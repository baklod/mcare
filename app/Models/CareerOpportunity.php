<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerOpportunity extends Model
{
    use HasFactory;

    public const TYPE_FULL_TIME = 'full-time';

    public const TYPE_PART_TIME = 'part-time';

    public const TYPE_CONTRACT = 'contract';

    public const TYPE_TEMPORARY = 'temporary';

    protected $fillable = [
        'created_by_id',
        'title',
        'employer',
        'location',
        'employment_type',
        'description',
        'requirements',
        'application_url',
        'application_email',
        'application_deadline',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'application_deadline' => 'datetime',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function employmentTypes(): array
    {
        return [
            self::TYPE_FULL_TIME => 'Full-time',
            self::TYPE_PART_TIME => 'Part-time',
            self::TYPE_CONTRACT => 'Contract',
            self::TYPE_TEMPORARY => 'Temporary',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopeVisibleToAlumni(Builder $query): Builder
    {
        // Expired listings stay in the admin history but leave the alumni feed.
        return $query
            ->where('is_published', true)
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('application_deadline')
                    ->orWhere('application_deadline', '>=', now());
            });
    }

    public function employmentTypeLabel(): string
    {
        return self::employmentTypes()[$this->employment_type]
            ?? str($this->employment_type ?: 'Not specified')->headline()->toString();
    }

    public function isExpired(): bool
    {
        return $this->application_deadline?->isPast() ?? false;
    }
}
