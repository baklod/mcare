<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'year',
        'trainer_id',
        'is_active',
        'enrollment_starts_at',
        'enrollment_ends_at',
        'training_starts_at',
        'training_ends_at',
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
            'training_starts_at' => 'datetime',
            'training_ends_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(EnrollmentApplication::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function scopeAssignedTo(Builder $query, User $trainer): Builder
    {
        return $query->where('trainer_id', $trainer->id);
    }

    public function isAssignedTo(User $trainer): bool
    {
        return (int) $this->trainer_id === (int) $trainer->id;
    }

    /**
     * Return the trainer's current batch. Existing installations may have
     * batches created before trainer assignment existed, so the earliest
     * active unassigned batch remains a temporary compatibility fallback.
     * Admin assignment removes this ambiguity for the live system.
     */
    public static function assignedTo(User $trainer): ?self
    {
        $assigned = self::query()
            ->assignedTo($trainer)
            ->orderByDesc('is_active')
            ->orderByDesc('training_starts_at')
            ->orderByDesc('id')
            ->first();

        if ($assigned) {
            return $assigned;
        }

        $legacyActiveBatches = self::query()
            ->whereNull('trainer_id')
            ->where('is_active', true)
            ->orderBy('training_starts_at')
            ->orderBy('id')
            ->get();

        return $legacyActiveBatches->first();
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TrainingModule::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(TrainerAnnouncement::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function officialDocuments(): HasMany
    {
        return $this->hasMany(OfficialDocument::class);
    }

    public function documentExports(): HasMany
    {
        return $this->hasMany(BatchDocumentExport::class);
    }

    public static function active(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('enrollment_ends_at')
            ->first();
    }

    public static function openForEnrollment(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('enrollment_starts_at')
                    ->orWhere('enrollment_starts_at', '<=', now());
            })
            ->where('enrollment_ends_at', '>', now())
            ->orderBy('enrollment_ends_at')
            ->first();
    }

    public function acceptsEnrollment(): bool
    {
        return $this->is_active
            && (! $this->enrollment_starts_at || $this->enrollment_starts_at->isPast())
            && $this->enrollment_ends_at->isFuture();
    }

    public function enrollmentState(): string
    {
        if (! $this->is_active) {
            return 'disabled';
        }

        if ($this->enrollment_starts_at?->isFuture()) {
            return 'upcoming';
        }

        return $this->enrollment_ends_at->isFuture() ? 'open' : 'closed';
    }

    public function enrollmentStateLabel(): string
    {
        return match ($this->enrollmentState()) {
            'open' => 'Open for enrollment',
            'upcoming' => 'Enrollment starting soon',
            'closed' => 'Enrollment closed',
            default => 'Not accepting enrollment',
        };
    }

    public function trainingState(): string
    {
        if (! $this->training_starts_at) {
            return 'not_scheduled';
        }

        if ($this->training_starts_at->isFuture()) {
            return 'not_started';
        }

        return $this->training_ends_at?->isPast() ? 'completed' : 'in_progress';
    }

    public function trainingStateLabel(): string
    {
        return match ($this->trainingState()) {
            'not_started' => 'Training not started',
            'in_progress' => 'Training in progress',
            'completed' => 'Training completed',
            default => 'Training dates not set',
        };
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
