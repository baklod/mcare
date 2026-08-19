<?php

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\OfficialDocument;
use App\Models\Quiz;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingModule;
use App\Services\CompletionEligibilityService;
use App\Services\TrainingCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

class TraineeDashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        return $this->portalView($request, 'trainee.dashboard');
    }

    public function modules(Request $request): View|RedirectResponse
    {
        return $this->portalView($request, 'trainee.modules.index');
    }

    public function stream(Request $request): View|RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee classroom opens after admin approval.');
        }

        $application->load('batch');

        return view('trainee.stream', [
            'application' => $application,
            'announcements' => $this->visibleAnnouncementsFor($application)
                ->with(['batch', 'trainer'])
                ->orderByDesc('is_pinned')
                ->orderByDesc('posted_at')
                ->paginate(15),
            'upcomingModules' => $this->availableModulesFor($application)
                ->limit(5)
                ->get(),
            'upcomingQuizzes' => $this->availableQuizzesFor($application)
                ->where(fn ($query) => $query->whereNull('due_at')->orWhere('due_at', '>=', now()))
                ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function schedule(Request $request, TrainingCalendarService $calendarService): View|RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee dashboard opens after admin approval.');
        }

        $application->load('batch');
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : $calendarService->suggestedMonth($application->batch);
        $sessions = $application->batch
            ? $calendarService->month($application->batch, $month, $application->schedule_preference)
            : collect();

        return $this->portalView($request, 'trainee.schedule', [
            'calendarMonth' => $month,
            'calendarSessions' => $sessions,
            'calendarSelectedDate' => $validated['date'] ?? null,
        ], $application);
    }

    public function payments(Request $request): View|RedirectResponse
    {
        return $this->portalView($request, 'trainee.payments');
    }

    public function documents(
        Request $request,
        CompletionEligibilityService $eligibility,
    ): View|RedirectResponse {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee dashboard opens after admin approval.');
        }

        return $this->portalView($request, 'trainee.documents', [
            'cotc' => OfficialDocument::query()
                ->where('enrollment_application_id', $application->id)
                ->where('type', OfficialDocument::TYPE_COTC)
                ->where('status', '!=', OfficialDocument::STATUS_REVOKED)
                ->latest('version')
                ->first(),
            'completionEligibility' => $eligibility->evaluate($application),
        ], $application);
    }

    private function portalView(
        Request $request,
        string $view,
        array $extraData = [],
        ?EnrollmentApplication $resolvedApplication = null,
    ): View|RedirectResponse {
        $application = $resolvedApplication ?? $this->approvedApplicationFor($request);

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

        return view($view, array_merge([
            'application' => $application,
            'batch' => $application->batch,
            'modules' => $modules,
            'progressByModule' => $progressByModule,
            'announcements' => $this->visibleAnnouncementsFor($application)
                ->with(['batch', 'trainer'])
                ->orderByDesc('is_pinned')
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
        ], $extraData));
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

        // The protected content URL can be opened directly by the browser, so
        // it must create the same progress baseline as the viewer page.
        $progress = ModuleProgress::query()->firstOrCreate([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
        ]);
        if ($progress->wasRecentlyCreated || $progress->status === ModuleProgress::STATUS_NOT_STARTED) {
            $progress->forceFill([
                'status' => ModuleProgress::STATUS_IN_PROGRESS,
                'progress_percent' => 10,
                'first_opened_at' => $progress->first_opened_at ?: now(),
            ]);
        }
        $progress->forceFill(['last_viewed_at' => now()])->save();

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

    public function securityEvent(Request $request, TrainingModule $module): Response
    {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);
        $validated = $request->validate([
            'event' => ['required', 'in:context_menu,print_shortcut,save_shortcut,before_print'],
        ]);

        // This records browser-side deterrence events. It cannot observe actions below the browser layer.
        AdminActivityLog::record($request->user(), 'trainee.module.restricted-action', $module, [
            'trainee_email' => $application->email,
            'event' => $validated['event'],
        ]);

        return response()->noContent();
    }

    private function authorizeModule(?EnrollmentApplication $application, TrainingModule $module): void
    {

        abort_unless($application, 403);
        abort_unless($module->is_published, 404);
        abort_if($module->available_at?->isFuture(), 404);
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
            ->where(fn ($query) => $query
                ->whereNull('available_at')
                ->orWhere('available_at', '<=', now()))
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
            ->orderBy('position')
            ->latest('published_at');
    }

    private function visibleAnnouncementsFor(EnrollmentApplication $application)
    {
        return TrainerAnnouncement::query()
            ->where('is_published', true)
            ->whereIn('audience', ['all', 'trainees'])
            ->where(fn ($query) => $query
                ->whereNull('posted_at')
                ->orWhere('posted_at', '<=', now()))
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->where(function ($query) use ($application) {
                $query->whereNull('training_batch_id')
                    ->orWhere('training_batch_id', $application->training_batch_id);
            });
    }

    private function availableQuizzesFor(EnrollmentApplication $application)
    {
        return Quiz::query()
            ->with(['trainer', 'batch'])
            ->released()
            ->where(function ($query) use ($application) {
                $query->where('target_enrollment_application_id', $application->id)
                    ->orWhere(function ($batchQuery) use ($application) {
                        $batchQuery->whereNull('target_enrollment_application_id')
                            ->where(function ($scopeQuery) use ($application) {
                                $scopeQuery->whereNull('training_batch_id')
                                    ->orWhere('training_batch_id', $application->training_batch_id);
                            });
                    });
            });
    }
}
