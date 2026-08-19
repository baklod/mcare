<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TraineeCompetencyRecord extends Model
{
    use HasFactory;

    public const STATUS_NOT_ASSESSED = 'not_assessed';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPETENT = 'competent';

    public const STATUS_NOT_YET_COMPETENT = 'not_yet_competent';

    protected $fillable = [
        'enrollment_application_id',
        'competency_unit_id',
        'status',
        'percentage_score',
        'tor_grade',
        'notes',
        'assessed_by_id',
        'assessed_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'percentage_score' => 'decimal:2',
            'tor_grade' => 'decimal:2',
            'assessed_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_NOT_ASSESSED => 'Not assessed',
            self::STATUS_IN_PROGRESS => 'In progress',
            self::STATUS_COMPETENT => 'Competent',
            self::STATUS_NOT_YET_COMPETENT => 'Not yet competent',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'enrollment_application_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(CompetencyUnit::class, 'competency_unit_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by_id');
    }

    public function outcomeResults(): HasMany
    {
        return $this->hasMany(TraineeOutcomeResult::class);
    }
}
