<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $activeBatch = TrainingBatch::active();

        $modules = collect([
            [
                'title' => 'Basic Patient Care and Safety',
                'training' => 'Caregiving NC II',
                'status' => 'In progress',
                'progress' => 60,
            ],
            [
                'title' => 'Caregiving Communication Skills',
                'training' => 'Caregiving NC II',
                'status' => 'Upcoming',
                'progress' => 78,
            ],
            [
                'title' => 'Elderly Care Fundamentals',
                'training' => 'Caregiving NC II',
                'status' => 'Upcoming',
                'progress' => 44,
            ],
        ]);

        // The trainer dashboard focuses on enrolled learners, not applicant intake.
        $assignedTrainees = EnrollmentApplication::query()
            ->with(['batch', 'user'])
            ->whereIn('status', [
                EnrollmentApplication::STATUS_PRE_ENLISTMENT,
                EnrollmentApplication::STATUS_APPROVED,
            ])
            ->latest()
            ->limit(5)
            ->get()
            ->values();

        $progressRows = $assignedTrainees->map(function (EnrollmentApplication $application, int $index) {
            $fallbackProgress = [60, 40, 100, 25, 80][$index] ?? 55;
            $progress = $application->status === EnrollmentApplication::STATUS_APPROVED
                ? max($fallbackProgress, 78)
                : $fallbackProgress;

            return [
                'name' => trim($application->first_name.' '.$application->last_name),
                'email' => $application->email,
                'training' => $application->program ?: 'Caregiving NC II',
                'schedule' => $application->batch?->scheduleLabelFor($application->schedule_preference) ?? $application->schedule_preference,
                'progress' => $progress,
                'status' => match (true) {
                    $progress >= 100 => 'Completed',
                    $progress <= 25 => 'Not Started',
                    default => 'In Progress',
                },
            ];
        });

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $progressRows = $progressRows->filter(function (array $row) use ($search) {
                return str_contains(strtolower($row['name']), strtolower($search))
                    || str_contains(strtolower($row['training']), strtolower($search));
            })->values();
        }

        $averageProgress = (int) round($progressRows->avg('progress') ?: 0);
        $batchLabel = $activeBatch ? $activeBatch->name.' '.$activeBatch->year : 'Batch 1 2026';
        $morningRoom = $activeBatch?->roomFor('AM') ?: 'Room 201 / Skills Lab';
        $afternoonRoom = $activeBatch?->roomFor('PM') ?: 'Room 202 / Lecture Room';

        $todaySessions = [
            [
                'time' => '9:00 AM',
                'title' => 'Caregiving Communication Skills',
                'type' => 'Live session',
                'batch' => $batchLabel,
                'duration' => '1h 30m',
                'room' => $morningRoom,
            ],
            [
                'time' => '2:00 PM',
                'title' => 'Elderly Care Fundamentals',
                'type' => 'Workshop',
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
            $needsAction = $row['status'] !== 'Completed';

            return [
                ...$row,
                'initial' => mb_strtoupper(mb_substr($row['name'], 0, 1)),
                'needs_action' => $needsAction,
                'action' => match ($row['status']) {
                    'Not Started' => 'Start learner follow-up',
                    'Completed' => 'Review completion',
                    default => 'Review progress',
                },
                'priority' => $row['progress'] <= 25 ? 'Overdue' : ($needsAction ? 'Needs action' : 'On track'),
            ];
        });

        return view('trainer.dashboard', [
            'activeBatch' => $activeBatch,
            'averageProgress' => $averageProgress,
            'learnerFollowUps' => $learnerFollowUps,
            'modules' => $modules,
            'progressRows' => $progressRows,
            'search' => $search,
            'stats' => [
                'total_trainings' => $modules->count(),
                'total_trainees' => EnrollmentApplication::query()
                    ->whereIn('status', [
                        EnrollmentApplication::STATUS_PRE_ENLISTMENT,
                        EnrollmentApplication::STATUS_APPROVED,
                    ])
                    ->count(),
                'sessions_today' => count($todaySessions),
                'average_progress' => $averageProgress,
            ],
            'teachingTimeline' => $teachingTimeline,
            'todaySessions' => $todaySessions,
        ]);
    }
}
