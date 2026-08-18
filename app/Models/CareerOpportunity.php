<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerOpportunity extends Model
{
    use HasFactory;

    public const GENDER_FEMALE = 'female';

    public const GENDER_MALE = 'male';

    public const GENDER_NOT_SPECIFIED = 'not-specified';

    public const MOBILITY_AMBULATORY = 'ambulatory';

    public const MOBILITY_BEDRIDDEN = 'bedridden';

    protected $fillable = [
        'created_by_id',
        'estimated_start_date',
        'patient_gender',
        'mobility_status',
        'patient_age',
        'specific_contraptions',
        'condition_summary',
        // These neutral values keep records compatible with the original schema.
        'title',
        'employer',
        'description',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_start_date' => 'date',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function patientGenders(): array
    {
        return [
            self::GENDER_FEMALE => 'Female',
            self::GENDER_MALE => 'Male',
            self::GENDER_NOT_SPECIFIED => 'Not specified',
        ];
    }

    /** @return array<string, string> */
    public static function mobilityStatuses(): array
    {
        return [
            self::MOBILITY_AMBULATORY => 'Ambulatory',
            self::MOBILITY_BEDRIDDEN => 'Bedridden',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopeVisibleToAlumni(Builder $query): Builder
    {
        // Legacy broad job records stay private until an admin supplies the approved fields.
        return $query
            ->where('is_published', true)
            ->whereNotNull('estimated_start_date')
            ->whereNotNull('patient_gender')
            ->whereNotNull('mobility_status')
            ->whereDate('estimated_start_date', '>=', today());
    }

    public function patientGenderLabel(): string
    {
        return self::patientGenders()[$this->patient_gender]
            ?? str($this->patient_gender ?: 'Not specified')->headline()->toString();
    }

    public function mobilityStatusLabel(): string
    {
        return self::mobilityStatuses()[$this->mobility_status]
            ?? str($this->mobility_status ?: 'Not specified')->headline()->toString();
    }
}
