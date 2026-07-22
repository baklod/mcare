<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Services\TrainingCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

class TrainerDashboardController extends Controller
{
    public function __invoke(Request $request, TrainingCalendarService $scheduleService): View
    {
        $trainer = $request->user();
        $activeBatch = TrainingBatch::active();

        // Approved applications in the active batch are the trainer's official learner list.
        $assignedTrainees = $this->approvedTraineesFor($activeBatch)
            ->limit(20)
            ->get()
            ->values();

        $progressRows = $assignedTrainees->map(function (EnrollmentApplication $application) {
            return [
                'name' => trim($application->first_name.' '.$application->last_name),
                'email' => $application->email,
                'training' => $application->program ?: 'Caregiving NC II',
                'schedule' => $application->batch?->scheduleLabelFor($application->schedule_preference)
                    ?? $application->schedule_preference,
                'status' => 'Assigned',
            ];
        });

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $progressRows = $progressRows->filter(function (array $row) use ($search) {
                return str_contains(strtolower($row['name']), strtolower($search))
                    || str_contains(strtolower($row['training']), strtolower($search));
            })->values();
        }

        // The dashboard only needs the count; the full module list belongs to
        // Resources. Avoid loading file metadata and batch relations here.
        $moduleCount = TrainingModule::query()
            ->where('trainer_id', $trainer->id)
            ->count();

        // Today's timeline is derived directly from the active admin-managed batch schedule.
        $todaySessions = $activeBatch ? $scheduleService->today($activeBatch) : collect();
        $teachingTimeline = $todaySessions->map(function (array $session) {
            $state = now()->gte($session['ends_at'])
                ? 'complete'
                : (now()->between($session['starts_at'], $session['ends_at']) ? 'current' : 'upcoming');

            return [
                'time' => $session['time'],
                'title' => $session['title'],
                'training' => $session['batch'],
                'duration' => $session['duration'],
                'room' => $session['room'],
                'state' => $state,
                'label' => match ($state) {
                    'complete' => 'Completed',
                    'current' => 'In progress',
                    default => 'Upcoming',
                },
            ];
        });

        $learnerFollowUps = $progressRows->map(function (array $row) {
            return [
                ...$row,
                'initial' => mb_strtoupper(mb_substr($row['name'], 0, 1)),
                'needs_action' => false,
                'action' => $row['schedule'] ?: 'Assigned to the current training batch',
                'priority' => 'On track',
            ];
        });

        // Give trainers a concise view of operational changes that can affect
        // their delivery day. We intentionally expose only admin-owned
        // actions (not authentication or document/security events).
        $systemNotifications = AdminActivityLog::query()
            ->with('user')
            ->where(function ($query) {
                $query->where('action', 'like', 'batch.%')
                    ->orWhere('action', 'like', 'admin.module.%')
                    ->orWhere('action', 'like', 'enrollment.review.%');
            })
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (AdminActivityLog $log) {
                $action = str($log->action)->replace(['.', '_', '-'], ' ')->headline()->toString();
                $subject = data_get($log->meta, 'name')
                    ?? data_get($log->meta, 'title')
                    ?? data_get($log->meta, 'after.name')
                    ?? data_get($log->meta, 'applicant_email')
                    ?? ($log->subject_type ? str($log->subject_type)->classBasename()->headline()->toString() : null);

                return [
                    'title' => $subject ? $action.' — '.$subject : $action,
                    'actor' => $log->user?->name ?? 'MCARE admin',
                    'occurred_at' => $log->created_at?->diffForHumans() ?? 'Recently',
                ];
            })
            ->values();

        return view('trainer.dashboard', [
            'activeBatch' => $activeBatch,
            'learnerFollowUps' => $learnerFollowUps,
            'moduleCount' => $moduleCount,
            'progressRows' => $progressRows,
            'search' => $search,
            'stats' => [
                'total_trainings' => $moduleCount,
                'total_trainees' => $assignedTrainees->count(),
                'sessions_today' => $todaySessions->count(),
            ],
            'teachingTimeline' => $teachingTimeline,
            'todaySessions' => $todaySessions,
            'systemNotifications' => $systemNotifications,
        ]);
    }

    public function storeModule(Request $request): RedirectResponse
    {
        $activeBatch = TrainingBatch::active();
        $request->merge([
            'audience_type' => $request->input('audience_type', 'batch'),
            'training_batch_id' => $request->input('training_batch_id', $activeBatch?->id),
        ]);
        $safeText = ['not_regex:/[<>"\'`;{}|\\\\]/u'];
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160', ...$safeText],
            'description' => ['required', 'string', 'max:1200', ...$safeText],
            'module_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,mp4,webm', 'extensions:pdf,jpg,jpeg,png,webp,mp4,webm', 'max:102400'],
            'audience_type' => ['required', Rule::in(['batch', 'trainee'])],
            'training_batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'target_enrollment_application_id' => [
                'nullable',
                'integer',
                Rule::exists('enrollment_applications', 'id')->where(
                    fn ($query) => $query->where('status', EnrollmentApplication::STATUS_APPROVED)
                ),
            ],
        ], [
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
            'module_file.mimes' => 'Training modules must be PDF, JPG, PNG, WEBP, MP4, or WEBM files.',
            'module_file.max' => 'Training modules must not exceed 100MB.',
        ]);

        $file = $request->file('module_file');
        $trainer = $request->user();
        $targetTrainee = $validated['audience_type'] === 'trainee'
            ? EnrollmentApplication::query()->find($validated['target_enrollment_application_id'] ?? null)
            : null;
        $batchId = $targetTrainee?->training_batch_id ?? ($validated['training_batch_id'] ?? null);

        if (! $batchId || ($validated['audience_type'] === 'trainee' && ! $targetTrainee)) {
            throw ValidationException::withMessages([
                'audience_type' => 'Choose a batch or an approved trainee before publishing.',
            ]);
        }

        // Keep trainer materials private so authorization remains enforceable on download.
        $path = $file->store("training-modules/{$trainer->id}", 'local');

        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batchId,
            'target_enrollment_application_id' => $targetTrainee?->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'file_path' => $path,
            'original_file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'is_published' => true,
            'published_at' => now(),
        ]);

        AdminActivityLog::record($trainer, 'trainer.module.uploaded', $module, [
            'title' => $module->title,
            'batch_id' => $batchId,
            'audience' => $targetTrainee ? 'trainee' : 'batch',
            'target_trainee_id' => $targetTrainee?->id,
        ]);

        return redirect()
            ->route('trainer.resources')
            ->with('saved', $targetTrainee
                ? 'Training module published for the selected trainee.'
                : 'Training module published for the selected batch.');
    }

    public function viewModule(Request $request, TrainingModule $module): View
    {
        abort_unless($module->trainer_id === $request->user()->id, 403);

        AdminActivityLog::record($request->user(), 'trainer.module.preview.opened', $module, [
            'title' => $module->title,
        ]);

        return view('trainer.modules.show', ['module' => $module->load(['batch', 'targetTrainee', 'progressRecords.application'])]);
    }

    public function moduleContent(Request $request, TrainingModule $module): BinaryFileResponse
    {
        abort_unless($module->trainer_id === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($module->file_path), 404);

        AdminActivityLog::record($request->user(), 'trainer.module.content.viewed', $module, [
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

    private function approvedTraineesFor(?TrainingBatch $activeBatch)
    {
        return EnrollmentApplication::query()
            ->with(['batch', 'user'])
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($activeBatch, fn ($query) => $query->where('training_batch_id', $activeBatch->id))
            ->orderBy('last_name')
            ->orderBy('first_name');
    }
}
