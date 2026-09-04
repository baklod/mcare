<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_password_login_skips_the_email_code_even_when_two_factor_is_enabled(): void
    {
        config()->set([
            'services.two_factor.enabled' => true,
            'services.two_factor.roles' => ['admin'],
        ]);
        Mail::fake();

        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'Password123',
            'role' => 'admin',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $admin->email,
                'password' => 'Password123',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionMissing('admin.mfa.pending');

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
        Mail::assertNothingSent();
    }

    public function test_admin_role_is_exempt_from_the_sign_in_code_on_the_shared_login_form(): void
    {
        Mail::fake();
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'Password123',
            'role' => 'admin',
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'Password123',
        ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionMissing('admin.mfa.pending');

        $this->assertAuthenticatedAs($admin);
        Mail::assertNothingSent();
    }
}
