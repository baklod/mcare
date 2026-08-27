<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'trainer_id',
        'training_batch_id',
        'target_enrollment_application_id',
        'training_module_id',
        'title',
        'instructions',
        'is_published',
        'published_at',
        'available_at',
        'due_at',
        'time_limit_minutes',
        'attempt_limit',
        'passing_score_percent',
        'requires_time_in',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'available_at' => 'datetime',
            'due_at' => 'datetime',
            'time_limit_minutes' => 'integer',
            'attempt_limit' => 'integer',
            'passing_score_percent' => 'decimal:2',
            'requires_time_in' => 'boolean',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function trainingModule(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }

    public function targetTrainee(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'target_enrollment_application_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(TraineeAttendance::class, 'quiz_id');
    }

    public function attendanceFor(EnrollmentApplication $application): ?TraineeAttendance
    {
        return $this->attendances->firstWhere('enrollment_application_id', $application->id)
            ?? $this->attendances()->where('enrollment_application_id', $application->id)->first();
    }

    public function isTimeInAllowed(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        if (! $this->requires_time_in) {
            return false;
        }

        if (! $this->isReleasedAt($at)) {
            return false;
        }

        if ($this->due_at && $this->due_at->lt($at)) {
            return false;
        }

        return true;
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(ClassroomComment::class, 'commentable');
    }

    public function scopeReleased(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('is_published', true)
            ->where(fn (Builder $builder) => $builder
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', $at))
            ->where(fn (Builder $builder) => $builder
                ->whereNull('available_at')
                ->orWhere('available_at', '<=', $at));
    }

    public function isReleasedAt(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->is_published
            && (! $this->published_at || $this->published_at->lte($at))
            && (! $this->available_at || $this->available_at->lte($at));
    }

    public function isOpenAt(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->isReleasedAt($at)
            && (! $this->due_at || $this->due_at->gte($at));
    }

    public function targets(EnrollmentApplication $application): bool
    {
        if ($application->is_historical_record) {
            return false;
        }

        if ($this->training_module_id !== null) {
            return ModuleProgress::query()
                ->where('enrollment_application_id', $application->id)
                ->where('training_module_id', $this->training_module_id)
                ->whereNotNull('unlocked_at')
                ->where('status', '!=', ModuleProgress::STATUS_LOCKED)
                ->exists();
        }

        if ($this->target_enrollment_application_id !== null) {
            return (int) $this->target_enrollment_application_id === $application->getKey();
        }

        return $this->training_batch_id === null
            || (int) $this->training_batch_id === (int) $application->training_batch_id;
    }

    public function attemptsRemainingFor(EnrollmentApplication $application): int
    {
        $usedAttempts = $this->attempts()
            ->where('enrollment_application_id', $application->getKey())
            ->count();

        return max(0, $this->attempt_limit - $usedAttempts);
    }
}
