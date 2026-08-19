<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchDocumentExport extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'training_batch_id',
        'type',
        'status',
        'total_records',
        'processed_records',
        'storage_disk',
        'file_path',
        'requested_by_id',
        'completed_at',
        'expires_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function isDownloadable(): bool
    {
        return $this->status === self::STATUS_READY
            && filled($this->file_path)
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
