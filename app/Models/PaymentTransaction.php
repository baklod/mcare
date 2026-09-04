<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_PENDING = 'pending_verification';

    public const STATUS_REJECTED = 'rejected';

    public const TYPE_DOWNPAYMENT = 'downpayment';

    public const TYPE_INSTALLMENT = 'installment';

    public const TYPE_FULL_PAYMENT = 'full_payment';

    public const TYPE_BALANCE = 'balance_settlement';

    public const CHANNEL_ONSITE = 'onsite';

    public const CHANNEL_ONLINE = 'online';

    protected $fillable = [
        'enrollment_application_id',
        'user_id',
        'ticket_number',
        'reference_number',
        'recorded_by_admin_id',
        'transaction_type',
        'payment_channel',
        'amount',
        'or_number',
        'receipt_proof_path',
        'status',
        'paid_at',
        'verified_at',
        'verified_by_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function isOnsiteTicket(): bool
    {
        return $this->payment_channel === self::CHANNEL_ONSITE
            && $this->status === self::STATUS_PENDING
            && filled($this->ticket_number);
    }

    public function referenceLabel(): string
    {
        return $this->reference_number
            ?: $this->ticket_number
            ?: $this->or_number
            ?: 'Reference pending';
    }

    public static function types(): array
    {
        return [
            self::TYPE_DOWNPAYMENT => 'Downpayment',
            self::TYPE_INSTALLMENT => 'Monthly Installment',
            self::TYPE_FULL_PAYMENT => 'Full Payment',
            self::TYPE_BALANCE => 'Balance Settlement',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->transaction_type] ?? str($this->transaction_type)->headline()->toString();
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_PENDING => 'Pending Verification',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function enrollmentApplication(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recordedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_admin_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }
}
