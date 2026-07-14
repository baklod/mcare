<?php

namespace Tests;

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
}
