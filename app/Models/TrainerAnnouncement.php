<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerAnnouncement extends Model
{
    use HasFactory;

    public const KIND_ANNOUNCEMENT = 'announcement';

    public const KIND_NEWS = 'news';

    public const KIND_REMINDER = 'reminder';

    protected $fillable = [
        'trainer_id',
        'training_batch_id',
        'title',
        'message',
        'audience',
        'kind',
        'is_pinned',
        'is_published',
        'posted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
            'posted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public static function kinds(): array
    {
        return [
            self::KIND_ANNOUNCEMENT,
            self::KIND_NEWS,
            self::KIND_REMINDER,
        ];
    }

    public function isVisibleNow(): bool
    {
        return $this->is_published
            && (! $this->posted_at || $this->posted_at->isPast())
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }
}
