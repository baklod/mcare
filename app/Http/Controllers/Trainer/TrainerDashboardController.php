<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Services\TrainerScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainerDashboardController extends Controller
{
    public function __invoke(Request $request, TrainerScheduleService $scheduleService): View
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

        // Published modules remain backed by the private LMS storage introduced on the review branch.
        $modules = TrainingModule::query()
            ->with('batch')
            ->where('trainer_id', $trainer->id)
            ->latest('published_at')
            ->get()
            ->map(function (TrainingModule $module) {
                return [
                    'id' => $module->id,
                    'title' => $module->title,
                    'training' => $module->batch
                        ? $module->batch->name.' '.$module->batch->year
                        : 'Caregiving NC II',
                    'file' => $module->original_file_name,
                    'published_at' => $module->published_at?->format('M j, Y') ?? 'Not published',
                    'status' => $module->is_published ? 'Published' : 'Draft',
                ];
            });

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

        return view('trainer.dashboard', [
            'activeBatch' => $activeBatch,
            'learnerFollowUps' => $learnerFollowUps,
            'modules' => $modules,
            'progressRows' => $progressRows,
            'search' => $search,
            'stats' => [
                'total_trainings' => $modules->count(),
                'total_trainees' => $assignedTrainees->count(),
                'sessions_today' => $todaySessions->count(),
            ],
            'teachingTimeline' => $teachingTimeline,
            'todaySessions' => $todaySessions,
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
            'module_file' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:20480'],
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
            'module_file.mimes' => 'Training modules must be PDF, DOC, DOCX, PPT, or PPTX files.',
            'module_file.max' => 'Training modules must not exceed 20MB.',
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
            'mime_type' => $file->getClientMimeType(),
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

    public function downloadModule(Request $request, TrainingModule $module): StreamedResponse
    {
        abort_unless($module->trainer_id === $request->user()->id, 403);

        AdminActivityLog::record($request->user(), 'trainer.module.downloaded', $module, [
            'title' => $module->title,
        ]);

        return Storage::disk('local')->download($module->file_path, $module->original_file_name);
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
