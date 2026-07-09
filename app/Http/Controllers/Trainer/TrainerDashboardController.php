<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use Illuminate\View\View;

class TrainerDashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeBatch = TrainingBatch::active();

        $modules = [
            [
                'title' => 'Basic Patient Care and Safety',
                'description' => 'Core caregiving routines, safety checks, and patient handling basics.',
                'status' => 'In Progress',
                'lessons' => 8,
                'completed_lessons' => 4,
                'progress' => 60,
            ],
            [
                'title' => 'Caregiving Communication Skills',
                'description' => 'Communication practices for clients, families, and care teams.',
                'status' => 'Ready',
                'lessons' => 6,
                'completed_lessons' => 5,
                'progress' => 78,
            ],
            [
                'title' => 'Elderly Care Fundamentals',
                'description' => 'Daily care support, dignity, comfort, and observation routines.',
                'status' => 'Review',
                'lessons' => 7,
                'completed_lessons' => 3,
                'progress' => 44,
            ],
        ];

        // The trainer dashboard should focus on assigned learner progress, not applicant intake.
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

        $averageProgress = (int) round($progressRows->avg('progress') ?: 0);

        $todaySessions = [
            [
                'time' => '09:00 AM',
                'title' => 'Caregiving Communication Skills',
                'type' => 'Live Session',
                'batch' => $activeBatch ? $activeBatch->name.' '.$activeBatch->year : 'Batch 1 2026',
                'duration' => '1h 30m',
                'room' => $activeBatch?->roomFor('AM') ?: 'Room 201 / Skills Lab',
            ],
            [
                'time' => '02:00 PM',
                'title' => 'Elderly Care Fundamentals',
                'type' => 'Workshop',
                'batch' => $activeBatch ? $activeBatch->name.' '.$activeBatch->year : 'Batch 1 2026',
                'duration' => '2h',
                'room' => $activeBatch?->roomFor('PM') ?: 'Room 202 / Lecture Room',
            ],
        ];

        $announcements = [
            ['title' => 'New training module available', 'body' => 'Advanced Elderly Care is ready for trainer review.', 'date' => now()->subDays(1)->format('M d, Y'), 'tone' => 'purple'],
            ['title' => 'Schedule update', 'body' => 'Use the active AM/PM batch schedule for learner reminders.', 'date' => now()->subDays(2)->format('M d, Y'), 'tone' => 'emerald'],
            ['title' => 'Certificate readiness', 'body' => 'Approved and paid learners can be prepared for future record generation.', 'date' => now()->subDays(4)->format('M d, Y'), 'tone' => 'amber'],
        ];

        return view('trainer.dashboard', [
            'activeBatch' => $activeBatch,
            'announcements' => $announcements,
            'averageProgress' => $averageProgress,
            'currentModule' => $modules[0],
            'modules' => $modules,
            'progressRows' => $progressRows,
            'stats' => [
                'total_trainings' => count($modules),
                'total_trainees' => EnrollmentApplication::query()
                    ->whereIn('status', [
                        EnrollmentApplication::STATUS_PRE_ENLISTMENT,
                        EnrollmentApplication::STATUS_APPROVED,
                    ])
                    ->count(),
                'sessions_today' => count($todaySessions),
                'average_progress' => $averageProgress,
            ],
            'todaySessions' => $todaySessions,
        ]);
    }
}
