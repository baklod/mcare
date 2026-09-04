<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'total_program_fee',
        'downpayment_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_program_fee' => 'decimal:2',
            'downpayment_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(EnrollmentApplication::class);
    }

    public function admissionApplications(): HasMany
    {
        return $this->hasMany(AdmissionApplication::class);
    }
}
