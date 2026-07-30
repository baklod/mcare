<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    use HasFactory;

    public const STATUS_CREATING = 'creating';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'enrollment_application_id',
        'provider',
        'merchant_reference',
        'idempotency_key',
        'provider_checkout_id',
        'provider_payment_id',
        'provider_payment_intent_id',
        'amount_minor',
        'currency',
        'status',
        'checkout_url',
        'livemode',
        'paid_at',
        'expired_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'livemode' => 'boolean',
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function enrollmentApplication(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class);
    }
}
