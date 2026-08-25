<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\LmsModulePublished;
use App\Rules\TrainingModuleFileType;
use App\Services\ClassroomComments;
use App\Support\TrainingModuleFiles;
use App\Services\TrainingCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
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
        $activeBatch = TrainingBatch::assignedTo($trainer);

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
            ->when($activeBatch, fn ($query) => $query->where('training_batch_id', $activeBatch->id))
            ->when(! $activeBatch, fn ($query) => $query->whereRaw('1 = 0'))
            ->count();

        $quizCount = Quiz::query()
            ->where('trainer_id', $trainer->id)
            ->when($activeBatch, fn ($query) => $query->where('training_batch_id', $activeBatch->id))
            ->when(! $activeBatch, fn ($query) => $query->whereRaw('1 = 0'))
            ->count();

        $activeQuizCount = Quiz::query()
            ->where('trainer_id', $trainer->id)
            ->where('is_published', true)
            ->when($activeBatch, fn ($query) => $query->where('training_batch_id', $activeBatch->id))
            ->when(! $activeBatch, fn ($query) => $query->whereRaw('1 = 0'))
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
            'quizCount' => $quizCount,
            'activeQuizCount' => $activeQuizCount,
            'progressRows' => $progressRows,
            'search' => $search,
            'stats' => [
                'total_trainings' => $moduleCount,
                'total_trainees' => $assignedTrainees->count(),
                'sessions_today' => $todaySessions->count(),
                'total_quizzes' => $quizCount,
                'active_quizzes' => $activeQuizCount,
            ],
            'teachingTimeline' => $teachingTimeline,
            'todaySessions' => $todaySessions,
            'systemNotifications' => $systemNotifications,
        ]);
    }

    public function storeModule(Request $request): RedirectResponse
    {
        $activeBatch = TrainingBatch::assignedTo($request->user());
        $request->merge([
            'audience_type' => $request->input('audience_type', 'batch'),
            'training_batch_id' => $request->input('training_batch_id', $activeBatch?->id),
        ]);
        $safeText = ['not_regex:/[<>"\'`;{}|\\\\]/u'];
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160', ...$safeText],
            'description' => ['required', 'string', 'max:1200', ...$safeText],
            'module_file' => ['required', 'file', 'max:'.TrainingModuleFiles::MAX_UPLOAD_KB, new TrainingModuleFileType],
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
            'module_file.max' => 'Training modules must not exceed 38MB on the current MCARE server.',
            'module_file.uploaded' => 'The upload did not reach MCARE. Check the server upload limit and try a smaller file.',
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

        if (! $activeBatch || (int) $batchId !== (int) $activeBatch->id) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Learning materials can only be published to the trainer\'s assigned batch.',
            ]);
        }

        if ($targetTrainee && (int) $targetTrainee->training_batch_id !== (int) $activeBatch->id) {
            throw ValidationException::withMessages([
                'target_enrollment_application_id' => 'The selected trainee is outside the trainer\'s assigned batch.',
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

        $traineeIds = $targetTrainee
            ? collect([$targetTrainee->user_id])->filter()
            : EnrollmentApplication::query()
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->where('training_batch_id', $batchId)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->unique();

        $trainees = User::query()
            ->where('role', 'trainee')
            ->whereIn('id', $traineeIds)
            ->get();

        if ($trainees->isNotEmpty()) {
            Notification::send($trainees, new LmsModulePublished($module));
        }

        return redirect()
            ->route('trainer.resources')
            ->with('saved', $targetTrainee
                ? 'Training module published for the selected trainee.'
                : 'Training module published for the selected batch.');
    }

    public function viewModule(
        Request $request,
        TrainingModule $module,
        ClassroomComments $comments,
    ): View
    {
        abort_unless($module->trainer_id === $request->user()->id, 403);

        AdminActivityLog::record($request->user(), 'trainer.module.preview.opened', $module, [
            'title' => $module->title,
        ]);

        $activeBatch = $module->batch ?? TrainingBatch::assignedTo($request->user());
        $trainees = $activeBatch
            ? $this->approvedTraineesFor($activeBatch)
                ->whereHas('moduleProgress', fn ($query) => $query
                    ->where('training_module_id', $module->id))
                ->get()
            : collect();
        $progressRecords = $module->progressRecords()->with(['application', 'evaluator'])->get();
        $progressByApp = $progressRecords->keyBy('enrollment_application_id');
        $quizzes = $module->quizzes()->with(['questions', 'attempts.application'])->get();

        return view('trainer.modules.show', [
            'module' => $module->load(['batch', 'targetTrainee']),
            'trainees' => $trainees,
            'progressByApp' => $progressByApp,
            'quizzes' => $quizzes,
            'classroomComments' => $comments->visibleFor($request->user(), $module),
            'privateCommentRecipients' => $comments->privateRecipients($request->user(), $module),
        ]);
    }

    public function supplementaryDownload(Request $request, TrainingModule $module, int $index): BinaryFileResponse
    {
        abort_unless($module->trainer_id === $request->user()->id, 403);
        $list = $module->supplementaryList();
        abort_unless(isset($list[$index]), 404);

        $attachment = $list[$index];
        $path = $attachment['file_path'] ?? null;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        AdminActivityLog::record($request->user(), 'trainer.module.supplementary.downloaded', $module, [
            'filename' => $attachment['original_name'] ?? 'supplementary',
        ]);

        $filename = basename($attachment['original_name'] ?? 'attachment');
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => ($attachment['mime_type'] ?? null) ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename, $fallbackFilename),
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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

        return $this->moduleFileResponse($module, HeaderUtils::DISPOSITION_INLINE, $filename, $fallbackFilename);
    }

    public function moduleDownload(Request $request, TrainingModule $module): BinaryFileResponse
    {
        abort_unless($module->trainer_id === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($module->file_path), 404);

        AdminActivityLog::record($request->user(), 'trainer.module.content.downloaded', $module, [
            'mime_type' => $module->mime_type,
        ]);

        $filename = basename($module->original_file_name);
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        return $this->moduleFileResponse($module, HeaderUtils::DISPOSITION_ATTACHMENT, $filename, $fallbackFilename);
    }

    private function moduleFileResponse(
        TrainingModule $module,
        string $disposition,
        string $filename,
        string $fallbackFilename,
    ): BinaryFileResponse {
        return response()->file(Storage::disk('local')->path($module->file_path), [
            'Content-Type' => $module->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename, $fallbackFilename),
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function approvedTraineesFor(?TrainingBatch $activeBatch)
    {
        return EnrollmentApplication::query()
            ->with(['batch', 'user'])
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($activeBatch, fn ($query) => $query->where('training_batch_id', $activeBatch->id))
            ->when(! $activeBatch, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('last_name')
            ->orderBy('first_name');
    }
}
