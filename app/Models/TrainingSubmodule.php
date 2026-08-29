<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSubmodule extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_module_id',
        'competency_outcome_id',
        'title',
        'description',
        'position',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }

    public function competencyOutcome(): BelongsTo
    {
        return $this->belongsTo(CompetencyOutcome::class, 'competency_outcome_id');
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(TrainingSubmoduleProgress::class, 'training_submodule_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'training_submodule_id');
    }
}
