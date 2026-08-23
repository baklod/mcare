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
