<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'trainer_id',
        'training_batch_id',
        'target_enrollment_application_id',
        'title',
        'description',
        'topic',
        'file_path',
        'original_file_name',
        'mime_type',
        'file_size',
        'is_published',
        'published_at',
        'available_at',
        'due_at',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'available_at' => 'datetime',
            'due_at' => 'datetime',
            'position' => 'integer',
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
}
