<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    use HasFactory;

    public const EXPIRATION_SUBMISSION_GRACE_SECONDS = 15;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_GRADED = 'graded';

    protected $fillable = [
        'quiz_id',
        'enrollment_application_id',
        'attempt_number',
        'status',
        'answers',
        'earned_points',
        'total_points',
        'score_percent',
        'passed',
        'started_at',
        'submitted_at',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'answers' => 'array',
            'earned_points' => 'decimal:2',
            'total_points' => 'decimal:2',
            'score_percent' => 'decimal:2',
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'enrollment_application_id');
    }

    public function isGraded(): bool
    {
        return $this->status === self::STATUS_GRADED;
    }

    public function effectiveEndsAt(): ?CarbonInterface
    {
        $quiz = $this->quiz;
        $endsAt = $quiz->due_at?->copy();
        $startedAt = $this->started_at ?? $this->created_at;

        if ($quiz->time_limit_minutes && $startedAt) {
            $timerEndsAt = $startedAt->copy()->addMinutes($quiz->time_limit_minutes);

            if (! $endsAt || $timerEndsAt->lt($endsAt)) {
                $endsAt = $timerEndsAt;
            }
        }

        return $endsAt;
    }

    public function remainingSeconds(?CarbonInterface $at = null): ?int
    {
        $endsAt = $this->effectiveEndsAt();

        if (! $endsAt) {
            return null;
        }

        $at ??= now();

        return max(0, (int) floor($at->diffInSeconds($endsAt, false)));
    }

    public function isExpiredAt(?CarbonInterface $at = null): bool
    {
        $endsAt = $this->effectiveEndsAt();

        if (! $endsAt) {
            return false;
        }

        return $endsAt->lte($at ?? now());
    }

    public function acceptsExpirationSubmissionAt(?CarbonInterface $at = null): bool
    {
        $endsAt = $this->effectiveEndsAt();

        if (! $endsAt) {
            return true;
        }

        $at ??= now();

        return $at->lte($endsAt->copy()->addSeconds(self::EXPIRATION_SUBMISSION_GRACE_SECONDS));
    }
}
