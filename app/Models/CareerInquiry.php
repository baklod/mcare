<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerInquiry extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'career_opportunity_id',
        'user_id',
        'name',
        'email',
        'contact_number',
        'message',
        'status',
        'admin_notes',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CareerOpportunity::class, 'career_opportunity_id');
    }

    public function graduate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
