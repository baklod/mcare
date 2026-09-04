<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetencyOutcome extends Model
{
    use HasFactory;

    protected $fillable = [
        'competency_unit_id',
        'title',
        'sort_order',
        'is_required',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(CompetencyUnit::class, 'competency_unit_id');
    }

    public function traineeResults(): HasMany
    {
        return $this->hasMany(TraineeOutcomeResult::class);
    }

    public function submodules(): HasMany
    {
        return $this->hasMany(TrainingSubmodule::class, 'competency_outcome_id');
    }
}
