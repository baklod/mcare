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
        'estimated_hours',
        'sort_order',
        'is_required',
        'is_tor_included',
        'is_selectable',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_tor_included' => 'boolean',
            'is_selectable' => 'boolean',
            'estimated_hours' => 'integer',
        ];
    }

    public static function categoryLabels(): array
    {
        return [
            'core' => 'Core competencies (TESDA TOR)',
            'common' => 'Common competencies',
            'basic' => 'Basic competencies',
            'custom' => 'Institutional / custom',
        ];
    }

    public function suggestedHours(): int
    {
        if ($this->estimated_hours) {
            return (int) $this->estimated_hours;
        }

        return match ($this->category) {
            'core' => 40,
            'common' => 20,
            'basic' => 18,
            default => 20,
        };
    }

    public function outcomeTitles(): array
    {
        return $this->outcomes->pluck('title')->filter()->values()->all();
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
