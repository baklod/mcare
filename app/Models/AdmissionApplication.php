<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdmissionApplication extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    public const EMAIL_IN_USE_MESSAGE = 'This Gmail has already been used for a pending or approved MCARE application.';

    protected $fillable = [
        'application_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'contact_number',
        'schedule_preference',
        'educational_attainment',
        'notes',
        'training_program_id',
        'program',
        'status',
        'privacy_consent_at',
        'admin_notes',
        'reviewed_at',
        'reviewed_by_id',
    ];

    protected function casts(): array
    {
        return [
            'privacy_consent_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DENIED => 'Denied',
        ];
    }

    public static function educationalAttainmentOptions(): array
    {
        return [
            'No Grade Completed',
            'Elementary Undergraduate',
            'Elementary Graduate',
            'High School Undergraduate',
            'High School Graduate',
            'Junior High (K-12)',
            'Senior High (K-12)',
            'Post-Secondary/Technical Vocational Undergraduate',
            'Post-Secondary/Technical Vocational Graduate',
            'College Undergraduate',
            'College Graduate',
            'Masteral',
            'Doctorate',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    public function fullName(): string
    {
        return trim(collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->implode(' '));
    }

    public static function generateNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $number = 'MCA-'.now()->year.'-'.$suffix;
        } while (self::query()->where('application_number', $number)->exists());

        return $number;
    }

    public static function normalizeNumber(?string $value): string
    {
        $compact = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $value) ?? '');

        if (preg_match('/^MCA(\d{4})([A-Z0-9]{6})$/', $compact, $matches) === 1) {
            return 'MCA-'.$matches[1].'-'.$matches[2];
        }

        return strtoupper(trim((string) $value));
    }

    public static function findByNumber(?string $value): ?self
    {
        $number = self::normalizeNumber($value);

        if ($number === '') {
            return null;
        }

        return self::query()->where('application_number', $number)->first();
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function enrollment(): HasOne
    {
        return $this->hasOne(EnrollmentApplication::class);
    }

    public function enrollmentUrl(): string
    {
        return route('enrollment.create', ['application_number' => $this->application_number]);
    }
}
