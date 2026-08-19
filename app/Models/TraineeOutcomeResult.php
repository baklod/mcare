<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraineeOutcomeResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'trainee_competency_record_id',
        'competency_outcome_id',
        'status',
        'assessed_by_id',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return ['assessed_at' => 'datetime'];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(TraineeCompetencyRecord::class, 'trainee_competency_record_id');
    }

    public function outcome(): BelongsTo
    {
        return $this->belongsTo(CompetencyOutcome::class, 'competency_outcome_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by_id');
    }
}
