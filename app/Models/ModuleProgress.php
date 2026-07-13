<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleProgress extends Model
{
    use HasFactory;

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $table = 'module_progress';

    protected $fillable = [
        'enrollment_application_id',
        'training_module_id',
        'status',
        'progress_percent',
        'first_opened_at',
        'last_viewed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'first_opened_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'enrollment_application_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }
}
