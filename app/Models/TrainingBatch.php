<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
        'is_active',
        'enrollment_starts_at',
        'enrollment_ends_at',
        'am_start_time',
        'am_end_time',
        'am_room',
        'am_days',
        'pm_start_time',
        'pm_end_time',
        'pm_room',
        'pm_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'enrollment_starts_at' => 'datetime',
            'enrollment_ends_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(EnrollmentApplication::class);
    }

    public static function active(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('enrollment_ends_at')
            ->first();
    }

    public function scheduleLabelFor(?string $preference): string
    {
        return match ($preference) {
            'AM' => $this->scheduleBlock($this->am_days, $this->am_start_time, $this->am_end_time),
            'PM' => $this->scheduleBlock($this->pm_days, $this->pm_start_time, $this->pm_end_time),
            default => 'Schedule to be confirmed by admin',
        };
    }

    public function dayPatternFor(?string $preference): ?string
    {
        return match ($preference) {
            'AM' => $this->am_days,
            'PM' => $this->pm_days,
            default => null,
        };
    }

    public function roomFor(?string $preference): ?string
    {
        return match ($preference) {
            'AM' => $this->am_room,
            'PM' => $this->pm_room,
            default => null,
        };
    }

    private function scheduleBlock(?string $days, ?string $start, ?string $end): string
    {
        $timeRange = $this->timeRange($start, $end);

        return filled($days)
            ? trim($days).' | '.$timeRange
            : $timeRange;
    }

    private function timeRange(?string $start, ?string $end): string
    {
        if (! $start || ! $end) {
            return 'Time to be confirmed by admin';
        }

        return now()->setTimeFromTimeString($start)->format('g:i A')
            .' - '.
            now()->setTimeFromTimeString($end)->format('g:i A');
    }
}
