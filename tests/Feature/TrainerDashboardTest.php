<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_trainer_login(): void
    {
        $this->get(route('trainer.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_non_trainer_cannot_open_trainer_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($user)
            ->get(route('trainer.dashboard'))
            ->assertForbidden();
    }

    public function test_trainer_can_open_lms_dashboard(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $html = $this->actingAs($trainer)
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('Teaching day')
            ->assertSee('Learning delivery')
            ->assertSee('Delivery snapshot')
            ->assertSee('Learner follow-up')
            ->assertSee('data-dashboard-sidebar-collapse', false)
            ->assertSee('id="trainer-dashboard-sidebar"', false)
            ->assertSee('Official trainer system', false)
            ->assertSee('Search pages, people, modules...')
            ->assertDontSee('Open stream')
            ->assertDontSee('Open modules')
            ->assertDontSee('data-dashboard-menu-open', false)
            ->getContent();

        $this->assertLessThan(
            strpos($html, "Today's schedule"),
            strpos($html, 'Learning delivery'),
            'Learning delivery should appear above the teaching schedule.'
        );
    }
}
