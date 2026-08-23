<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleProgress extends Model
{
    use HasFactory;

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
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
        'status',
        'progress_percent',
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
}
