<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingSubmoduleProgress extends Model
{
    use HasFactory;

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_AWAITING_EVALUATION = 'awaiting_evaluation';
    public const STATUS_NEEDS_REMEDIATION = 'needs_remediation';
    public const STATUS_COMPLETED = 'completed';

    protected $table = 'training_submodule_progress';

    protected $fillable = [
        'enrollment_application_id',
        'training_submodule_id',
        'status',
        'progress_percent',
        'first_opened_at',
        'last_viewed_at',
        'submitted_at',
        'quiz_score',
        'practical_rating',
        'competency_outcome',
        'evaluation_remarks',
        'evaluated_by_id',
        'evaluated_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'quiz_score' => 'decimal:2',
            'first_opened_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'evaluated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'enrollment_application_id');
    }

    public function submodule(): BelongsTo
    {
        return $this->belongsTo(TrainingSubmodule::class, 'training_submodule_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by_id');
    }

    public function isTrainerValidated(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->competency_outcome === ModuleProgress::OUTCOME_COMPETENT;
    }

    public function needsRemediation(): bool
    {
        return $this->status === self::STATUS_NEEDS_REMEDIATION
            || $this->competency_outcome === ModuleProgress::OUTCOME_NOT_YET_COMPETENT
            || $this->practical_rating === ModuleProgress::RATING_NOT_YET_COMPETENT;
    }

    public function workflowStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_NOT_STARTED => 'Ready to start',
            self::STATUS_AWAITING_EVALUATION => 'Awaiting trainer evaluation',
            self::STATUS_NEEDS_REMEDIATION => 'Needs remediation',
            self::STATUS_COMPLETED => 'Competent',
            default => 'In progress',
        };
    }
}
