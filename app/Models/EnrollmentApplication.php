<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentApplication extends Model
{
    use HasFactory;

    public const STATUS_PROFILE_SUBMITTED = 'profile_submitted';

    public const STATUS_PRE_ENLISTMENT = 'pre_enlistment';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    protected $fillable = [
        'user_id',
        'email',
        'program',
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
        'date_accomplished',
        'status',
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
            'reviewed_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
