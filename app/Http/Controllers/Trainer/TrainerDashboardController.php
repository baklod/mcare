<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainerDashboardController extends Controller
{
    public function __invoke(Request $request): View
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

        $batchLabel = $activeBatch ? $activeBatch->name.' '.$activeBatch->year : 'Current batch';
        $morningRoom = $activeBatch?->roomFor('AM') ?: 'Room 201 / Skills Lab';
        $afternoonRoom = $activeBatch?->roomFor('PM') ?: 'Room 202 / Lecture Room';

        $todaySessions = [
            [
                'time' => '9:00 AM',
                'title' => 'Caregiving Communication Skills',
                'batch' => $batchLabel,
                'duration' => '1h 30m',
                'room' => $morningRoom,
            ],
            [
                'time' => '2:00 PM',
                'title' => 'Elderly Care Fundamentals',
                'batch' => $batchLabel,
                'duration' => '2h',
                'room' => $afternoonRoom,
            ],
        ];

        $teachingTimeline = collect([
            [
                'time' => '8:00 AM',
                'title' => 'Preparation and setup',
                'training' => $batchLabel,
                'duration' => '30 min',
                'room' => $morningRoom,
                'state' => 'complete',
                'label' => 'Completed',
            ],
            [
                'time' => $todaySessions[0]['time'],
                'title' => $todaySessions[0]['title'],
                'training' => $todaySessions[0]['batch'],
                'duration' => $todaySessions[0]['duration'],
                'room' => $todaySessions[0]['room'],
                'state' => 'current',
                'label' => 'In progress',
            ],
            [
                'time' => $todaySessions[1]['time'],
                'title' => $todaySessions[1]['title'],
                'training' => $todaySessions[1]['batch'],
                'duration' => $todaySessions[1]['duration'],
                'room' => $todaySessions[1]['room'],
                'state' => 'upcoming',
                'label' => 'Upcoming',
            ],
            [
                'time' => '4:00 PM',
                'title' => 'Wrap-up and reflection',
                'training' => 'Trainer notes and learner follow-up',
                'duration' => '30 min',
                'room' => $morningRoom,
                'state' => 'upcoming',
                'label' => 'Upcoming',
            ],
        ]);

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
                'sessions_today' => count($todaySessions),
            ],
            'teachingTimeline' => $teachingTimeline,
            'todaySessions' => $todaySessions,
        ]);
    }

    public function storeModule(Request $request): RedirectResponse
    {
        $safeText = ['not_regex:/[<>"\'`;{}|\\\\]/u'];
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160', ...$safeText],
            'description' => ['required', 'string', 'max:1200', ...$safeText],
            'module_file' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:20480'],
        ], [
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
            'module_file.mimes' => 'Training modules must be PDF, DOC, DOCX, PPT, or PPTX files.',
            'module_file.max' => 'Training modules must not exceed 20MB.',
        ]);

        $file = $request->file('module_file');
        $trainer = $request->user();
        $activeBatch = TrainingBatch::active();

        // Keep trainer materials private so authorization remains enforceable on download.
        $path = $file->store("training-modules/{$trainer->id}", 'local');

        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $activeBatch?->id,
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
            'batch' => $activeBatch ? $activeBatch->name.' '.$activeBatch->year : null,
        ]);

        return redirect()
            ->to(route('trainer.dashboard').'#modules')
            ->with('saved', 'Training module published for trainees.');
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
