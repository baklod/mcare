<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_responses_include_global_security_headers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_private_routes_are_marked_noindex_and_no_store(): void
    {
        $response = $this->get('/admin/login');

        $response
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
            ->assertHeader('Referrer-Policy', 'no-referrer');

        // Cache-Control directive ordering can vary, so test the important value.
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }

    public function test_admin_login_is_throttled_after_repeated_failures(): void
    {
        $credentials = [
            'email' => 'attacker@example.com',
            'password' => 'definitely-wrong',
        ];

        // The strict limiter allows five attempts per minute for email + IP.
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/admin/login', $credentials)
                ->assertSessionHasErrors('email');
        }

        // The sixth request should be stopped before another password check.
        $this->post('/admin/login', $credentials)
            ->assertStatus(429);
    }

    public function test_oversized_admin_search_input_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from('/admin/logs')
            ->get('/admin/logs?search='.str_repeat('a', 101))
            ->assertRedirect('/admin/logs')
            ->assertSessionHasErrors('search');
    }
}
