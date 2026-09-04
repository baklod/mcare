<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalAlumniClaim extends Model
{
    use HasFactory;

    public const STATUS_PENDING_EMAIL = 'pending_email';

    public const STATUS_PENDING_ONSITE = 'pending_onsite';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'birth_date',
        'gender',
        'contact_number',
        'street',
        'barangay',
        'city',
        'province',
        'region',
        'zip_code',
        'educational_attainment',
        'school_name',
        'education_year_graduated',
        'training_completion_year',
        'historical_batch_name',
        'training_schedule',
        'evidence_type',
        'certificate_number',
        'tor_reference',
        'evidence_document_path',
        'evidence_document_page_2_path',
        'status',
        'privacy_consent_at',
        'verification_checks',
        'onsite_verified_at',
        'onsite_verified_by_id',
        'admin_notes',
        'reviewed_at',
        'reviewed_by_id',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'privacy_consent_at' => 'datetime',
            'verification_checks' => 'array',
            'onsite_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING_EMAIL => 'Pending email verification',
            self::STATUS_PENDING_ONSITE => 'Pending on-site verification',
            self::STATUS_APPROVED => 'Verified alumni',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function onsiteVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'onsite_verified_by_id');
    }
}
