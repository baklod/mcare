<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedAccountSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_role_login_urls_forward_to_the_shared_sign_in_page(): void
    {
        foreach (['admin.login', 'trainer.login', 'trainee.login'] as $routeName) {
            $this->get(route($routeName))
                ->assertRedirect(route('login'));
        }
    }

    public function test_shared_login_renders_the_responsive_brand_layout_and_all_account_paths(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-auth-dashboard-preview', false)
            ->assertSee('data-auth-form-card', false)
            ->assertSee('assets/login-dashboard-preview.png', false)
            ->assertSee(route('login.store'), false)
            ->assertSee(route('auth.google.redirect'), false)
            ->assertSee(route('applications.create'), false)
            ->assertSee(route('enrollment.create'), false)
            ->assertSee(route('alumni.claim.create'), false)
            ->assertSeeText('Sign in to your account')
            ->assertSeeText('Remember me')
            ->assertSeeText('Sign in with Google')
            ->assertSee('data-auth-login-form', false)
            ->assertSeeText('Sign In');
    }

    public function test_every_role_signs_out_through_the_shared_landing_flow(): void
    {
        foreach (['admin', 'trainer', 'trainee', 'alumni'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->post(route('logout'))
                ->assertRedirect(route('landing'))
                ->assertSessionHas('signed_out');

            $this->assertGuest();
        }
    }
}
