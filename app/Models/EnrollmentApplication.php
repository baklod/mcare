<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class EnrollmentApplication extends Model
{
    use HasFactory;

    public const STATUS_PROFILE_SUBMITTED = 'profile_submitted';

    public const STATUS_PRE_ENLISTMENT = 'pre_enlistment';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    public const LEARNING_ACTIVE = 'active';

    public const LEARNING_PAUSED = 'paused';

    public const LEARNING_GRADUATED = 'graduated';

    public const LEARNING_WITHDRAWN = 'withdrawn';

    public const PAYMENT_NOT_SELECTED = 'not_selected';

    public const PAYMENT_ONSITE_PENDING = 'onsite_pending';

    public const PAYMENT_ONLINE_PENDING = 'online_pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'email',
        'program',
        'training_batch_id',
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'birth_date',
        'birthplace_city',
        'birthplace_province',
        'birthplace_region',
        'gender',
        'civil_status',
        'employment_status',
        'employment_type',
        'contact_number',
        'nationality',
        'schedule_preference',
        'street',
        'barangay',
        'city',
        'province',
        'region',
        'zip_code',
        'educational_attainment',
        'school_name',
        'year_graduated',
        'guardian_name',
        'guardian_address',
        'classification',
        'disability_type',
        'disability_cause',
        'scholarship_type',
        'privacy_consent',
        'signature_name',
        'birth_certificate_path',
        'education_document_path',
        'good_moral_certificate_path',
        'id_photo_path',
        'signature_type',
        'signature_path',
        'document_review',
        'documents_reviewed_at',
        'documents_reviewed_by_id',
        'date_accomplished',
        'status',
        'learning_status',
        'learning_status_notes',
        'learning_status_changed_at',
        'learning_status_changed_by_id',
        'payment_method',
        'payment_status',
        'payment_amount',
        'payment_currency',
        'payment_reference',
        'payment_receipt_number',
        'payment_receipt_expires_at',
        'payment_selected_at',
        'paymongo_checkout_reference',
        'paymongo_checkout_url',
        'payment_meta',
        'payment_verified_by_id',
        'payment_verified_at',
        'payment_verification_notes',
        'admin_notes',
        'reviewed_at',
        'reviewed_by_id',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'date_accomplished' => 'date',
            'privacy_consent' => 'boolean',
            'payment_amount' => 'decimal:2',
            'payment_receipt_expires_at' => 'datetime',
            'payment_selected_at' => 'datetime',
            'payment_meta' => 'array',
            'payment_verified_at' => 'datetime',
            'document_review' => 'array',
            'documents_reviewed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'learning_status_changed_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PROFILE_SUBMITTED => 'Submitted',
            self::STATUS_PRE_ENLISTMENT => 'Pre-enlistment',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DENIED => 'Denied',
        ];
    }

    public static function reviewableStatuses(): array
    {
        return [
            self::STATUS_PRE_ENLISTMENT,
            self::STATUS_APPROVED,
            self::STATUS_DENIED,
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public static function learningStatuses(): array
    {
        return [
            self::LEARNING_ACTIVE => 'Active',
            self::LEARNING_PAUSED => 'Paused',
            self::LEARNING_GRADUATED => 'Graduated',
            self::LEARNING_WITHDRAWN => 'Withdrawn',
        ];
    }

    public function learningStatusLabel(): string
    {
        return self::learningStatuses()[$this->learning_status]
            ?? str($this->learning_status)->headline()->toString();
    }

    public static function paymentStatuses(): array
    {
        return [
            self::PAYMENT_NOT_SELECTED => 'Not selected',
            self::PAYMENT_ONSITE_PENDING => 'Pay on site',
            self::PAYMENT_ONLINE_PENDING => 'Online pending',
            self::PAYMENT_PAID => 'Paid',
            self::PAYMENT_EXPIRED => 'Expired',
        ];
    }

    public function paymentStatusLabel(): string
    {
        return self::paymentStatuses()[$this->payment_status] ?? str($this->payment_status)->headline()->toString();
    }

    public function hasActiveOnsiteReceipt(): bool
    {
        return $this->payment_method === 'onsite'
            && filled($this->payment_receipt_number)
            && $this->payment_receipt_expires_at
            && $this->payment_receipt_expires_at->isFuture();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by_id');
    }

    public function documentReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'documents_reviewed_by_id');
    }

    public function learningStatusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learning_status_changed_by_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function moduleProgress(): HasMany
    {
        return $this->hasMany(ModuleProgress::class, 'enrollment_application_id');
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function targetedQuizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'target_enrollment_application_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'enrollment_application_id');
    }

    public function effectivePaymentDeadline(): ?Carbon
    {
        $receiptDeadline = $this->payment_receipt_expires_at;
        $batchDeadline = $this->batch?->enrollment_ends_at;

        if ($receiptDeadline && $batchDeadline) {
            return $receiptDeadline->lte($batchDeadline) ? $receiptDeadline : $batchDeadline;
        }

        return $receiptDeadline ?: $batchDeadline;
    }
}
