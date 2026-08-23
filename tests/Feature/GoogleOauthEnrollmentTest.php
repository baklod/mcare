<?php

namespace Tests\Feature;

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
        $this->assertNotNull($user->email_verified_at);

        $this->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('Verified by Google')
            ->assertSee('value="maria.santos@gmail.com"', false)
            ->assertSee('value="Maria"', false)
            ->assertSee('value="Santos"', false)
            ->assertSee('autocomplete="section-applicant sex"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('Google OAuth is paused');
    }
}
