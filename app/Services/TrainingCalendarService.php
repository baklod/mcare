<?php

namespace App\Services;

use App\Models\TrainingBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TrainingCalendarService
{
    public function suggestedMonth(?TrainingBatch $batch, ?Carbon $reference = null): Carbon
    {
        $reference = ($reference ?? now())->copy();

        if (! $batch?->training_starts_at || ! $batch->training_ends_at) {
            return $reference->startOfMonth();
        }

        if ($reference->lt($batch->training_starts_at)) {
            return $batch->training_starts_at->copy()->startOfMonth();
        }

        if ($reference->gt($batch->training_ends_at)) {
            return $batch->training_ends_at->copy()->startOfMonth();
        }

        return $reference->startOfMonth();
    }

    /**
     * Expand one batch's recurring AM/PM rules into dated calendar events.
     */
    public function month(TrainingBatch $batch, Carbon $month, ?string $period = null): Collection
    {
        // Recurring rules are not calendar events until the admin sets both
        // training boundaries. This prevents unfinished batches from repeating forever.
        if (! $batch->training_starts_at || ! $batch->training_ends_at) {
            return collect();
        }

        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $rangeStart = $batch->training_starts_at->copy()->startOfDay();
        $rangeEnd = $batch->training_ends_at->copy()->endOfDay();
        $start = $rangeStart->greaterThan($monthStart) ? $rangeStart : $monthStart;
        $end = $rangeEnd->lessThan($monthEnd) ? $rangeEnd : $monthEnd;

        if ($start->greaterThan($end)) {
            return collect();
        }

        $requestedPeriod = strtoupper(trim((string) $period));
        $periods = in_array($requestedPeriod, ['AM', 'PM'], true)
            ? [$requestedPeriod]
            : ['AM', 'PM'];
        $sessions = collect();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            foreach ($periods as $sessionPeriod) {
                $this->appendSession($sessions, $batch, $date, $sessionPeriod);
            }
        }

        return $sessions
            ->when(
                $requestedPeriod === 'WEEKEND',
                fn (Collection $items) => $items->filter(
                    fn (array $session) => $session['date']->isWeekend()
                )
            )
            ->sortBy(fn (array $session) => $session['starts_at']->getTimestamp())
            ->values();
    }

    /**
     * Build one calendar stream for admin views that need multiple batches.
     */
    public function monthForBatches(iterable $batches, Carbon $month, ?string $period = null): Collection
    {
        return collect($batches)
            ->flatMap(fn (TrainingBatch $batch) => $this->month($batch, $month, $period))
            ->sortBy(fn (array $session) => sprintf(
                '%s-%06d-%s',
                $session['date_key'],
                $session['starts_at']->secondsSinceMidnight(),
                $session['batch'],
            ))
            ->values();
    }

    public function today(TrainingBatch $batch, ?string $period = null): Collection
    {
        return $this->month($batch, now()->startOfMonth(), $period)
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
        $batchLabel = trim($batch->name.' '.$batch->year);

        $sessions->push([
            'id' => 'batch-'.$batch->id.'-'.strtolower($period).'-'.$date->toDateString(),
            'date_key' => $date->toDateString(),
            'date' => $date->copy(),
            'period' => $period,
            'time' => $startsAt->format('g:i A'),
            'time_range' => $startsAt->format('g:i A').' - '.$endsAt->format('g:i A'),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration' => $startsAt->diffForHumans($endsAt, true),
            'room' => $room ?: 'Room to be confirmed',
            'batch_id' => $batch->id,
            'batch' => $batchLabel,
            'is_active_batch' => (bool) $batch->is_active,
            // Batch notes are administrative and must not leak into learner calendars.
            'title' => 'Caregiving NC II ('.$period.')',
            'calendar_title' => $batchLabel.' · '.$period,
            'updated_at' => $batch->updated_at,
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

        // Compact admin patterns use MWF and TTS, where the two Ts mean
        // Tuesday and Thursday. S remains Saturday and U represents Sunday.
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
