<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ApplicantEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_manual_account_is_sent_a_new_link_and_cannot_sign_in(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create([
            'email' => 'manual.applicant@gmail.com',
            'password' => 'Password123',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Password123',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        Notification::assertSentTo(
            $user,
            QueuedVerifyEmail::class,
            fn (QueuedVerifyEmail $notification): bool => $notification instanceof ShouldQueue
                && $notification->queue === 'mail',
        );
    }

    public function test_signed_email_link_verifies_manual_account(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
        );

        $this->get($url)
            ->assertRedirect(route('login'))
            ->assertSessionHas('verified');

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_verification_email_uses_the_official_mcare_mail_template(): void
    {
        $user = User::factory()->unverified()->create([
            'name' => 'Maria Santos',
        ]);

        $mail = (new QueuedVerifyEmail)->toMail($user);
        $html = view($mail->view, $mail->viewData)->render();

        $this->assertSame('Verify your email address', $mail->subject);
        $this->assertSame('mail.verify-email', $mail->view);
        $this->assertStringContainsString('Mission Care Training and Assessment Center', $html);
        $this->assertStringContainsString('Dear Maria Santos', $html);
        $this->assertStringContainsString('Verify your email address', $html);
        $this->assertStringContainsString('https://iili.io/nHTStnf.md.png', $html);
        $this->assertStringContainsString(e($mail->viewData['verificationUrl']), $html);
    }
}
