<?php

namespace Tests\Feature;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.two_factor.enabled' => true,
            'services.two_factor.roles' => ['admin'],
        ]);
    }

    public function test_admin_password_login_requires_email_code_before_authentication(): void
    {
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
            ->assertRedirect(route('login'))
            ->assertSessionHas('admin.mfa.pending');

        $this->assertGuest();
        $code = null;

        Mail::assertSent(TwoFactorCodeMail::class, function (TwoFactorCodeMail $mail) use ($admin, &$code): bool {
            $code = $mail->code;

            return $mail->hasTo($admin->email) && strlen($mail->code) === 6;
        });

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Verify your sign-in');

        $this->withSession(['url.intended' => route('admin.enrollments.index')])
            ->post(route('login.verify-2fa'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertSame($admin->id, session('admin.mfa.verified_user_id'));
    }

    public function test_public_account_login_cannot_bypass_admin_email_code(): void
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
            ->assertRedirect(route('login'))
            ->assertSessionHas('admin.mfa.pending');

        $this->assertGuest();
        Mail::assertSent(TwoFactorCodeMail::class);
    }
}
