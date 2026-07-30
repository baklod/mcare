<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson(route('api.v1.dashboard.summary'), ['Accept' => 'application/json']);
        $response->assertUnauthorized();
    }

    public function test_authenticated_trainee_receives_mobile_dashboard_json(): void
    {
        $user = User::factory()->create([
            'role' => 'trainee',
        ]);

        $response = $this->actingAs($user)->getJson(route('api.v1.dashboard.summary'));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.role', 'trainee');
    }
}
