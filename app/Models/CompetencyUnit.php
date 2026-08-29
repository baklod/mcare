<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetencyUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_code',
        'category',
        'code',
        'title',
        'sort_order',
        'is_required',
        'is_tor_included',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_tor_included' => 'boolean',
        ];
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(CompetencyOutcome::class)->orderBy('sort_order');
    }

    public function traineeRecords(): HasMany
    {
        return $this->hasMany(TraineeCompetencyRecord::class);
    }

    public function trainingModules(): HasMany
    {
        return $this->hasMany(TrainingModule::class, 'competency_unit_id');
    }
}
