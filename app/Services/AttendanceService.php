<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\TraineeAttendance;
use App\Models\TrainingBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceService
{
    /**
     * Save or update the daily attendance sheet for a batch.
     *
     * @param  array<int, array{status?: string, notes?: string|null}>  $records
     */
    public function saveDailyAttendance(
        TrainingBatch $batch,
        Carbon $date,
        array $records,
        User $recorder
    ): int {
        $savedCount = 0;
        $formattedDate = $date->toDateString();

        $enrolledIds = $batch->applications()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('learning_status', '!=', EnrollmentApplication::LEARNING_GRADUATED)
            ->pluck('id')
            ->all();

        foreach ($records as $appId => $record) {
            if (! in_array((int) $appId, $enrolledIds, true)) {
                continue;
            }

            $status = $record['status'] ?? TraineeAttendance::STATUS_PRESENT;
            if (! array_key_exists($status, TraineeAttendance::statuses())) {
                $status = TraineeAttendance::STATUS_PRESENT;
            }

            $notes = filled($record['notes'] ?? null) ? trim((string) $record['notes']) : null;
            $timedInAt = in_array($status, [TraineeAttendance::STATUS_PRESENT, TraineeAttendance::STATUS_LATE], true)
                ? now()
                : null;

            TraineeAttendance::updateOrCreate(
                [
                    'training_batch_id' => $batch->id,
                    'enrollment_application_id' => (int) $appId,
                    'attendance_date' => $formattedDate,
                    'quiz_id' => null,
                ],
                [
                    'status' => $status,
                    'check_in_type' => TraineeAttendance::TYPE_DAILY_SHEET,
                    'timed_in_at' => $timedInAt,
                    'notes' => $notes,
                    'recorded_by_id' => $recorder->id,
                ]
            );

            $savedCount++;
        }

        return $savedCount;
    }

    /**
     * Record a trainee's self time-in for an asynchronous quiz/activity.
     */
    public function recordActivityTimeIn(
        Quiz $quiz,
        EnrollmentApplication $application,
        Request $request
    ): TraineeAttendance {
        if (! $quiz->isTimeInAllowed()) {
            throw new \DomainException('Time-in is currently not allowed or the deadline has passed.');
        }

        $isBatchMember = $quiz->training_batch_id && (int) $application->training_batch_id === (int) $quiz->training_batch_id;
        $isTargetMember = (int) $quiz->target_enrollment_application_id === (int) $application->id;

        if (! $isBatchMember && ! $isTargetMember) {
            throw new \DomainException('You are not assigned to this activity.');
        }

        return TraineeAttendance::updateOrCreate(
            [
                'training_batch_id' => $application->training_batch_id,
                'enrollment_application_id' => $application->id,
                'attendance_date' => now()->toDateString(),
                'quiz_id' => $quiz->id,
            ],
            [
                'status' => TraineeAttendance::STATUS_PRESENT,
                'check_in_type' => TraineeAttendance::TYPE_ACTIVITY_TIME_IN,
                'timed_in_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => str($request->userAgent() ?? '')->limit(500)->toString(),
                'recorded_by_id' => $application->user_id,
                'notes' => 'Self time-in for activity: '.$quiz->title,
            ]
        );
    }

    /**
     * Compute attendance summary statistics for a batch.
     *
     * @return array{
     *     total_days: int,
     *     dates: list<string>,
     *     trainees: list<array<string, mixed>>,
     *     average_rate: float
     * }
     */
    public function getBatchSummary(TrainingBatch $batch): array
    {
        $trainees = $batch->applications()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('learning_status', '!=', EnrollmentApplication::LEARNING_GRADUATED)
            ->with(['user', 'attendances' => fn ($q) => $q->where('training_batch_id', $batch->id)])
            ->get();

        $distinctDates = TraineeAttendance::where('training_batch_id', $batch->id)
            ->whereNull('quiz_id')
            ->distinct()
            ->orderBy('attendance_date')
            ->pluck('attendance_date')
            ->map(fn ($d) => is_string($d) ? $d : $d->toDateString())
            ->values()
            ->all();

        $totalDays = count($distinctDates);
        $summaryTrainees = [];
        $totalRates = 0;

        foreach ($trainees as $trainee) {
            $attendances = $trainee->attendances->where('training_batch_id', $batch->id);
            $present = $attendances->where('status', TraineeAttendance::STATUS_PRESENT)->count();
            $late = $attendances->where('status', TraineeAttendance::STATUS_LATE)->count();
            $absent = $attendances->where('status', TraineeAttendance::STATUS_ABSENT)->count();
            $excused = $attendances->where('status', TraineeAttendance::STATUS_EXCUSED)->count();

            $totalSessions = $present + $late + $absent + $excused;
            $rate = $totalSessions > 0
                ? round((($present + $late) / $totalSessions) * 100, 1)
                : 100.0;

            $totalRates += $rate;

            $dateStatusMap = [];
            foreach ($distinctDates as $dateStr) {
                $att = $attendances->first(fn ($a) => (is_string($a->attendance_date) ? $a->attendance_date : $a->attendance_date?->toDateString()) === $dateStr && ! $a->quiz_id);
                $dateStatusMap[$dateStr] = $att?->status ?? '-';
            }

            $summaryTrainees[] = [
                'id' => $trainee->id,
                'name' => $trainee->full_name ?: ($trainee->user?->name ?? 'Trainee #'.$trainee->id),
                'email' => $trainee->email ?: $trainee->user?->email,
                'schedule' => $trainee->schedule_preference ? strtoupper($trainee->schedule_preference) : 'AM',
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'excused' => $excused,
                'total_sessions' => $totalSessions,
                'attendance_rate' => $rate,
                'is_compliant' => $rate >= 80.0,
                'by_date' => $dateStatusMap,
            ];
        }

        $averageRate = count($summaryTrainees) > 0
            ? round($totalRates / count($summaryTrainees), 1)
            : 100.0;

        return [
            'total_days' => $totalDays,
            'dates' => $distinctDates,
            'trainees' => $summaryTrainees,
            'average_rate' => $averageRate,
        ];
    }

    /**
     * Generate a downloadable CSV export of the batch attendance roster.
     */
    public function exportCsv(TrainingBatch $batch): StreamedResponse
    {
        $summary = $this->getBatchSummary($batch);
        $filename = 'mcare-attendance-'.str($batch->name)->slug().'-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($batch, $summary) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Header info
            fputcsv($handle, ['MISSION CARE TRAINING CENTER - CAREGIVING NC II']);
            fputcsv($handle, ['OFFICIAL ATTENDANCE ROSTER - '.strtoupper($batch->name)]);
            fputcsv($handle, ['Generated on: '.now()->format('F d, Y g:i A')]);
            fputcsv($handle, ['Total Training Sessions Recorded: '.$summary['total_days']]);
            fputcsv($handle, ['Average Batch Attendance: '.$summary['average_rate'].'%']);
            fputcsv($handle, []);

            // Column Headers
            $rowHeader = [
                'Trainee Name',
                'Email',
                'Schedule',
                'Present (P)',
                'Late (L)',
                'Absent (A)',
                'Excused (E)',
                'Total Sessions',
                'Attendance Rate (%)',
                'TESDA Status',
            ];

            foreach ($summary['dates'] as $date) {
                $rowHeader[] = Carbon::parse($date)->format('M d');
            }

            fputcsv($handle, $rowHeader);

            // Data Rows
            foreach ($summary['trainees'] as $t) {
                $row = [
                    $t['name'],
                    $t['email'],
                    $t['schedule'],
                    $t['present'],
                    $t['late'],
                    $t['absent'],
                    $t['excused'],
                    $t['total_sessions'],
                    $t['attendance_rate'].'%',
                    $t['is_compliant'] ? 'Compliant' : 'At Risk (<80%)',
                ];

                foreach ($summary['dates'] as $date) {
                    $st = $t['by_date'][$date] ?? '-';
                    $letter = match ($st) {
                        'present' => 'P',
                        'late' => 'L',
                        'absent' => 'A',
                        'excused' => 'E',
                        default => '-',
                    };
                    $row[] = $letter;
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
