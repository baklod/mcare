<?php

namespace App\Services;

use App\Models\TrainingBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TrainerScheduleService
{
    public function month(TrainingBatch $batch, Carbon $month): Collection
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $rangeStart = $batch->training_starts_at?->copy()->startOfDay() ?? $monthStart;
        $rangeEnd = $batch->training_ends_at?->copy()->endOfDay() ?? $monthEnd;
        $start = $rangeStart->greaterThan($monthStart) ? $rangeStart : $monthStart;
        $end = $rangeEnd->lessThan($monthEnd) ? $rangeEnd : $monthEnd;

        if ($start->greaterThan($end)) {
            return collect();
        }

        $sessions = collect();
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $this->appendSession($sessions, $batch, $date, 'AM');
            $this->appendSession($sessions, $batch, $date, 'PM');
        }

        return $sessions->values();
    }

    public function today(TrainingBatch $batch): Collection
    {
        return $this->month($batch, now()->startOfMonth())
            ->where('date_key', now()->toDateString())
            ->values();
    }

    private function appendSession(Collection $sessions, TrainingBatch $batch, Carbon $date, string $period): void
    {
        $days = $period === 'AM' ? $batch->am_days : $batch->pm_days;
        $allowedDays = $this->dayNumbers($days);

        if (! in_array($date->dayOfWeekIso, $allowedDays, true)) {
            return;
        }

        $start = $period === 'AM' ? $batch->am_start_time : $batch->pm_start_time;
        $end = $period === 'AM' ? $batch->am_end_time : $batch->pm_end_time;
        $room = $period === 'AM' ? $batch->am_room : $batch->pm_room;

        if (! $start || ! $end) {
            return;
        }

        $startsAt = $date->copy()->setTimeFromTimeString($start);
        $endsAt = $date->copy()->setTimeFromTimeString($end);
        $sessions->push([
            'date_key' => $date->toDateString(),
            'date' => $date->copy(),
            'period' => $period,
            'time' => $startsAt->format('g:i A'),
            'time_range' => $startsAt->format('g:i A').' - '.$endsAt->format('g:i A'),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration' => $startsAt->diffForHumans($endsAt, true),
            'room' => $room ?: 'Room to be confirmed',
            'batch' => $batch->name.' '.$batch->year,
            'title' => ($batch->notes ?: 'Caregiving NC II').' ('.$period.')',
        ]);
    }

    private function dayNumbers(?string $pattern): array
    {
        $pattern = strtoupper(trim((string) $pattern));
        if ($pattern === '') {
            return [];
        }

        $namedDays = [
            'MON' => 1, 'MONDAY' => 1, 'TUE' => 2, 'TUESDAY' => 2,
            'WED' => 3, 'WEDNESDAY' => 3, 'THU' => 4, 'THURSDAY' => 4,
            'FRI' => 5, 'FRIDAY' => 5, 'SAT' => 6, 'SATURDAY' => 6,
            'SUN' => 7, 'SUNDAY' => 7,
        ];
        $tokens = preg_split('/[\s,\/\-]+/', $pattern, -1, PREG_SPLIT_NO_EMPTY);
        $resolved = collect($tokens)->map(fn ($token) => $namedDays[$token] ?? null)->filter()->values();

        if ($resolved->isNotEmpty()) {
            return $resolved->unique()->all();
        }

        // Compact admin patterns use MWF and TTS, where the two Ts mean Tuesday and Thursday.
        $days = [];
        $tSeen = 0;
        foreach (str_split($pattern) as $letter) {
            $day = match ($letter) {
                'M' => 1,
                'T' => ++$tSeen === 1 ? 2 : 4,
                'W' => 3,
                'F' => 5,
                'S' => 6,
                'U' => 7,
                default => null,
            };
            if ($day !== null) {
                $days[] = $day;
            }
        }

        return array_values(array_unique($days));
    }
}
