<?php

namespace App\Support;

use App\Models\EnrollmentApplication;
use App\Models\User;

class AccountPortal
{
    public static function roleLabelFor(?User $user): string
    {
        if ($user?->isGraduate()) {
            return 'Alumni';
        }

        return match ($user?->role) {
            'admin' => 'Admin',
            'trainer' => 'Trainer',
            'trainee' => 'Approved Trainee',
            'alumni' => 'Alumni',
            default => 'Applicant',
        };
    }

    public static function ctaLabelFor(?User $user): string
    {
        if ($user?->isGraduate()) {
            return 'Open Alumni Career Hub';
        }

        return match ($user?->role) {
            'admin' => 'Open Admin Center',
            'trainer' => 'Open Trainer Portal',
            'trainee' => 'Open Trainee Portal',
            'alumni' => 'Open Alumni Portal',
            default => self::applicantWasDenied($user)
                ? 'Correct & Resubmit'
                : (self::applicantHasApplication($user) ? 'Continue Application' : 'Start Enrollment'),
        };
    }

    public static function urlFor(?User $user): string
    {
        return route(self::routeNameFor($user));
    }

    public static function routeNameFor(?User $user): string
    {
        return match ($user?->role) {
            'admin' => 'admin.dashboard',
            'trainer' => 'trainer.dashboard',
            'trainee' => 'trainee.dashboard',
            'alumni' => 'alumni.dashboard',
            default => self::applicantWasDenied($user)
                ? 'enrollment.create'
                : (self::applicantHasApplication($user) ? 'payment.show' : 'enrollment.create'),
        };
    }

    public static function applicantHasApplication(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // This keeps returning applicants on payment/review instead of sending them back to a blank form.
        return EnrollmentApplication::query()
            ->where('user_id', $user->id)
            ->exists();
    }

    public static function applicantWasDenied(?User $user): bool
    {
        if (! $user || $user->role !== 'applicant') {
            return false;
        }

        return EnrollmentApplication::query()
            ->where('user_id', $user->id)
            ->where('status', EnrollmentApplication::STATUS_DENIED)
            ->exists();
    }
}
