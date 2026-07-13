<?php

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

class TraineeDashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee dashboard opens after admin approval.');
        }

        $application->load('batch');
        $modules = $this->availableModulesFor($application)->get();
        $progressByModule = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('training_module_id', $modules->pluck('id'))
            ->get()
            ->keyBy('training_module_id');
        $progressPercent = $modules->isEmpty()
            ? 0
            : (int) round($modules->sum(fn ($module) => $progressByModule->get($module->id)?->progress_percent ?? 0) / $modules->count());

        return view('trainee.dashboard', [
            'application' => $application,
            'batch' => $application->batch,
            'modules' => $modules,
            'progressByModule' => $progressByModule,
            'announcements' => TrainerAnnouncement::query()
                ->with(['batch', 'trainer'])
                ->where(function ($query) use ($application) {
                    $query->whereNull('training_batch_id')
                        ->orWhere('training_batch_id', $application->training_batch_id);
                })
                ->latest('posted_at')
                ->take(5)
                ->get(),
            'stats' => [
                'progress' => $progressPercent,
                'modules' => $modules->count(),
                'documents' => collect([
                    $application->birth_certificate_path,
                    $application->education_document_path,
                    $application->good_moral_certificate_path,
                    $application->id_photo_path,
                    $application->signature_path,
                ])->filter()->count(),
                'payment' => $application->paymentStatusLabel(),
            ],
        ]);
    }

    public function viewModule(Request $request, TrainingModule $module): View
    {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);

        // Opening the protected viewer is a server-side progress event and does not depend on JavaScript.
        $progress = ModuleProgress::query()->firstOrCreate([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
        ]);
        if ($progress->wasRecentlyCreated || $progress->status === ModuleProgress::STATUS_NOT_STARTED) {
            $progress->forceFill([
                'status' => ModuleProgress::STATUS_IN_PROGRESS,
                'progress_percent' => 10,
                'first_opened_at' => now(),
            ])->save();
        }
        $progress->forceFill(['last_viewed_at' => now()])->save();

        AdminActivityLog::record($request->user(), 'trainee.module.viewer.opened', $module, [
            'trainee_email' => $application->email,
            'progress_status' => $progress->status,
        ]);

        return view('trainee.modules.show', compact('application', 'module', 'progress'));
    }

    public function moduleContent(Request $request, TrainingModule $module): BinaryFileResponse
    {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);
        abort_unless(Storage::disk('local')->exists($module->file_path), 404);

        ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $module->id)
            ->update(['last_viewed_at' => now()]);

        AdminActivityLog::record($request->user(), 'trainee.module.content.viewed', $module, [
            'trainee_email' => $application->email,
            'mime_type' => $module->mime_type,
            'range_request' => $request->hasHeader('Range'),
        ]);

        $filename = basename($module->original_file_name);
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        return response()->file(Storage::disk('local')->path($module->file_path), [
            'Content-Type' => $module->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename, $fallbackFilename),
            'Accept-Ranges' => 'bytes',
        ]);
    }

    public function updateProgress(Request $request, TrainingModule $module): RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);
        $validated = $request->validate(['action' => ['required', 'in:complete,reopen']]);
        $completed = $validated['action'] === 'complete';
        $progress = ModuleProgress::query()->firstOrNew([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
        ]);
        $progress->forceFill([
            'status' => $completed ? ModuleProgress::STATUS_COMPLETED : ModuleProgress::STATUS_IN_PROGRESS,
            'progress_percent' => $completed ? 100 : 10,
            'first_opened_at' => $progress->first_opened_at ?: now(),
            'last_viewed_at' => now(),
            'completed_at' => $completed ? now() : null,
        ])->save();

        AdminActivityLog::record($request->user(), 'trainee.module.progress.updated', $progress, [
            'module_id' => $module->id,
            'status' => $progress->status,
        ]);

        return back()->with('saved', $completed ? 'Module marked complete.' : 'Module returned to in progress.');
    }

    private function authorizeModule(?EnrollmentApplication $application, TrainingModule $module): void
    {

        abort_unless($application, 403);
        abort_unless($module->is_published, 404);
        abort_unless(
            $module->target_enrollment_application_id === $application->id
                || (
                    $module->target_enrollment_application_id === null
                    && ($module->training_batch_id === null || $module->training_batch_id === $application->training_batch_id)
                ),
            403
        );

    }

    private function approvedApplicationFor(Request $request): ?EnrollmentApplication
    {
        return EnrollmentApplication::query()
            ->where('user_id', $request->user()->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest()
            ->first();
    }

    private function availableModulesFor(EnrollmentApplication $application)
    {
        // Modules are scoped to the trainee's batch unless a trainer intentionally publishes globally.
        return TrainingModule::query()
            ->with(['trainer', 'batch'])
            ->where('is_published', true)
            ->where(function ($query) use ($application) {
                $query->where('target_enrollment_application_id', $application->id)
                    ->orWhere(function ($batchQuery) use ($application) {
                        $batchQuery->whereNull('target_enrollment_application_id')
                            ->where(function ($scopeQuery) use ($application) {
                                $scopeQuery->whereNull('training_batch_id')
                                    ->orWhere('training_batch_id', $application->training_batch_id);
                            });
                    });
            })
            ->latest('published_at');
    }
}
