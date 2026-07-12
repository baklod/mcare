<?php

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        return view('trainee.dashboard', [
            'application' => $application,
            'batch' => $application->batch,
            'modules' => $modules,
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
                'progress' => $modules->isEmpty() ? 0 : 0,
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

    public function downloadModule(Request $request, TrainingModule $module): StreamedResponse
    {
        $application = $this->approvedApplicationFor($request);

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

        AdminActivityLog::record($request->user(), 'trainee.module.downloaded', $module, [
            'title' => $module->title,
            'trainee_email' => $application->email,
        ]);

        return Storage::disk('local')->download($module->file_path, $module->original_file_name);
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
