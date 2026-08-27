<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraineeAttendance extends Model
{
    use HasFactory;

    public const STATUS_PRESENT = 'present';

    public const STATUS_LATE = 'late';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_EXCUSED = 'excused';

    public const TYPE_DAILY_SHEET = 'daily_sheet';

    public const TYPE_ACTIVITY_TIME_IN = 'activity_time_in';

    public const TYPE_ADMIN_OVERRIDE = 'admin_override';

    protected $fillable = [
        'training_batch_id',
        'enrollment_application_id',
        'attendance_date',
        'quiz_id',
        'status',
        'check_in_type',
        'timed_in_at',
        'ip_address',
        'user_agent',
        'notes',
        'recorded_by_id',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'timed_in_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'enrollment_application_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PRESENT => 'Present',
            self::STATUS_LATE => 'Late',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_EXCUSED => 'Excused',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline();
    }

    public function isCountedPresent(): bool
    {
        return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE], true);
    }

    public function scopeForBatch(Builder $query, int|TrainingBatch $batch): Builder
    {
        $batchId = $batch instanceof TrainingBatch ? $batch->id : $batch;

        return $query->where('training_batch_id', $batchId);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('attendance_date', $date);
    }
}
