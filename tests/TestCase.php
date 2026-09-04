<?php

namespace Tests;

use App\Models\AdmissionApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Test helpers that impersonate an administrator represent a completed
     * password + email-code login unless a test exercises the login flow.
     */
    public function actingAs($user, $guard = null): static
    {
        parent::actingAs($user, $guard);

        if ($user instanceof User && $user->hasRole('admin')) {
            $this->withSession([
                'admin.mfa.verified_user_id' => $user->getAuthIdentifier(),
            ]);
        }

        return $this;
    }

    protected function makeApprovedAdmission(array $overrides = []): AdmissionApplication
    {
        return AdmissionApplication::query()->create(array_merge([
            'application_number' => AdmissionApplication::generateNumber(),
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'email' => 'applicant@gmail.com',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'educational_attainment' => 'High School Graduate',
            'program' => 'Caregiving NC II',
            'status' => AdmissionApplication::STATUS_APPROVED,
            'privacy_consent_at' => now(),
            'reviewed_at' => now(),
        ], $overrides));
    }
}
