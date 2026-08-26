<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\HistoricalAlumniClaim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleOauthEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_is_blocked_when_the_callback_url_is_not_secure_and_exact(): void
    {
        config()->set([
            'app.url' => 'https://mcare-demo.ngrok-free.app',
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'https://mcare-demo.ngrok-free.app',
        ]);

        Socialite::shouldReceive('driver')->never();

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('auth_error');
    }

    public function test_new_google_applicant_is_verified_and_sent_to_prefilled_enrollment(): void
    {
        $googleUser = SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Maria Reyes Santos',
            'given_name' => 'Maria',
            'family_name' => 'Santos',
            'email' => 'maria.santos@gmail.com',
            'avatar' => 'https://example.test/maria.jpg',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('enrollment.create'))
            ->assertSessionHas('signed_in');

        $user = User::query()->where('email', 'maria.santos@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('https://example.test/maria.jpg', $user->avatar_url);
        $this->assertNotNull($user->email_verified_at);

        $this->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('Verified by Google')
            ->assertSee('value="maria.santos@gmail.com"', false)
            ->assertSee('value="Maria"', false)
            ->assertSee('value="Santos"', false)
            ->assertSee('https://example.test/maria.jpg', false)
            ->assertSee('autocomplete="section-applicant sex"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('Google OAuth is paused');
    }

    public function test_denied_google_applicant_can_sign_in_to_correct_and_resubmit(): void
    {
        $user = User::factory()->create([
            'name' => 'Denied Applicant',
            'email' => 'denied.applicant@gmail.com',
            'role' => 'applicant',
            'applicant_status' => EnrollmentApplication::STATUS_DENIED,
            'email_verified_at' => now(),
        ]);
        EnrollmentApplication::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Denied',
            'last_name' => 'Applicant',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_DENIED,
            'admin_notes' => 'Contact MCARE regarding the submitted document.',
            'total_paid_amount' => 2000,
            'downpayment_amount' => 2000,
            'payment_verified_at' => now(),
        ]);

        $googleUser = SocialiteUser::fake([
            'id' => 'google-denied-123',
            'name' => 'Denied Applicant',
            'email' => $user->email,
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('enrollment.create'))
            ->assertSessionHas('reapply_notice');

        $this->assertAuthenticatedAs($user);
        $this->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('Resubmit corrected enrollment')
            ->assertSee('Contact MCARE regarding the submitted document.')
            ->assertDontSee('Please wait while the administrator completes your account verification');
    }

    public function test_google_verifies_a_historical_claim_without_bypassing_onsite_review(): void
    {
        $user = User::factory()->unverified()->create([
            'name' => 'Legacy Claimant',
            'email' => 'legacy.claimant@gmail.com',
            'role' => 'applicant',
            'applicant_status' => 'historical_claim_pending_email',
        ]);
        HistoricalAlumniClaim::create([
            'user_id' => $user->id,
            'first_name' => 'Legacy',
            'last_name' => 'Claimant',
            'birth_date' => '1990-01-01',
            'gender' => 'Female',
            'contact_number' => '09171234567',
            'street' => '1 Archive Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'Archive School',
            'education_year_graduated' => 2007,
            'training_completion_year' => 2018,
            'training_schedule' => 'AM',
            'evidence_type' => 'certificate',
            'status' => HistoricalAlumniClaim::STATUS_PENDING_EMAIL,
            'privacy_consent_at' => now(),
        ]);

        $googleUser = SocialiteUser::fake([
            'id' => 'google-legacy-123',
            'name' => 'Legacy Claimant',
            'email' => 'legacy.claimant@gmail.com',
            'avatar' => 'https://example.test/legacy-avatar.jpg',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('verified');

        $this->assertGuest();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame('https://example.test/legacy-avatar.jpg', $user->fresh()->avatar_url);
        $this->assertSame(
            HistoricalAlumniClaim::STATUS_PENDING_ONSITE,
            $user->historicalAlumniClaim()->firstOrFail()->status,
        );
        $this->assertDatabaseMissing('enrollment_applications', ['user_id' => $user->id]);
    }
}
