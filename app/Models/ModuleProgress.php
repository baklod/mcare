<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleProgress extends Model
{
    use HasFactory;

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_AWAITING_EVALUATION = 'awaiting_evaluation';
    public const STATUS_NEEDS_REMEDIATION = 'needs_remediation';
    public const STATUS_COMPLETED = 'completed';

    public const RATING_COMPETENT = 'competent';
    public const RATING_NOT_YET_COMPETENT = 'not_yet_competent';
    public const RATING_PENDING = 'pending';

    public const OUTCOME_COMPETENT = 'competent';
    public const OUTCOME_NOT_YET_COMPETENT = 'not_yet_competent';
    public const OUTCOME_IN_PROGRESS = 'in_progress';

    protected $table = 'module_progress';

    protected $fillable = [
        'enrollment_application_id',
        'training_module_id',
        'sequence_number',
        'status',
        'progress_percent',
        'assigned_at',
        'unlocked_at',
        'submitted_at',
        'quiz_score',
        'practical_rating',
        'competency_outcome',
        'evaluation_remarks',
        'evaluated_by_id',
        'evaluated_at',
        'first_opened_at',
        'last_viewed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quiz_score' => 'decimal:2',
            'sequence_number' => 'integer',
            'assigned_at' => 'datetime',
            'unlocked_at' => 'datetime',
            'submitted_at' => 'datetime',
            'evaluated_at' => 'datetime',
            'first_opened_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'enrollment_application_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by_id');
    }

    public function practicalRatingLabel(): string
    {
        return match ($this->practical_rating) {
            self::RATING_COMPETENT => 'Competent (C)',
            self::RATING_NOT_YET_COMPETENT => 'Not Yet Competent (NYC)',
            default => 'Pending Evaluation',
        };
    }

    public function competencyOutcomeLabel(): string
    {
        return match ($this->competency_outcome) {
            self::OUTCOME_COMPETENT => 'Competent (Passed)',
            self::OUTCOME_NOT_YET_COMPETENT => 'For Remediation',
            default => 'In Progress',
        };
    }

    public function isAccessible(): bool
    {
        return $this->unlocked_at !== null && $this->status !== self::STATUS_LOCKED;
    }

    public function isTrainerValidated(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->competency_outcome === self::OUTCOME_COMPETENT;
    }

    public function workflowStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_LOCKED => 'Locked until the previous module is competent',
            self::STATUS_NOT_STARTED => 'Ready to start',
            self::STATUS_AWAITING_EVALUATION => 'Awaiting trainer evaluation',
            self::STATUS_NEEDS_REMEDIATION => 'Needs remediation',
            self::STATUS_COMPLETED => 'Competent',
            default => 'In progress',
        };
    }
}
