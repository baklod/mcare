<?php

namespace App\Models;

use App\Support\TrainingModuleFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TrainingModule extends Model
{
    use HasFactory;

    public const CATEGORY_CORE = 'core';
    public const CATEGORY_COMMON = 'common';
    public const CATEGORY_BASIC = 'basic';
    public const CATEGORY_CUSTOM = 'custom';

    public const DELIVERY_DRAFT = 'draft';
    public const DELIVERY_ACTIVE = 'active';
    public const DELIVERY_CLOSED = 'closed';

    protected $fillable = [
        'trainer_id',
        'training_batch_id',
        'target_enrollment_application_id',
        'module_code',
        'competency_category',
        'title',
        'description',
        'topic',
        'estimated_hours',
        'file_path',
        'original_file_name',
        'mime_type',
        'file_size',
        'supplementary_files',
        'is_published',
        'delivery_status',
        'published_at',
        'activated_at',
        'closed_at',
        'available_at',
        'due_at',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'activated_at' => 'datetime',
            'closed_at' => 'datetime',
            'available_at' => 'datetime',
            'due_at' => 'datetime',
            'position' => 'integer',
            'estimated_hours' => 'integer',
            'supplementary_files' => 'array',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function targetTrainee(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'target_enrollment_application_id');
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(ModuleProgress::class, 'training_module_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'training_module_id')->latest('id');
    }

    public function primaryQuiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'training_module_id')->latestOfMany();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(ClassroomComment::class, 'commentable');
    }

    public function categoryLabel(): string
    {
        return match ($this->competency_category) {
            self::CATEGORY_CORE => 'Core Competency',
            self::CATEGORY_COMMON => 'Common Competency',
            self::CATEGORY_BASIC => 'Basic Competency',
            default => 'Institutional Learning Module',
        };
    }

    public function supplementaryList(): array
    {
        if (! is_array($this->supplementary_files)) {
            return [];
        }

        return array_values(array_filter(
            $this->supplementary_files,
            fn ($file): bool => is_array($file) && filled($file['file_path'] ?? null),
        ));
    }

    public function scopeAvailableTo(Builder $query, EnrollmentApplication $application): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(fn (Builder $builder) => $builder
                ->whereNull('available_at')
                ->orWhere('available_at', '<=', now()))
            ->whereHas('progressRecords', fn (Builder $progress) => $progress
                ->where('enrollment_application_id', $application->id)
                ->whereNotNull('unlocked_at')
                ->where('status', '!=', ModuleProgress::STATUS_LOCKED));
    }

    public function deliveryStatusLabel(): string
    {
        return match ($this->delivery_status) {
            self::DELIVERY_ACTIVE => 'Active module',
            self::DELIVERY_CLOSED => 'Closed to new enrollees',
            default => 'Draft',
        };
    }

    public function previewKind(): string
    {
        return TrainingModuleFiles::previewKind($this);
    }

    public function fileTypeLabel(): string
    {
        return TrainingModuleFiles::typeLabel($this);
    }
}
