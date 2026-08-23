<?php

namespace Tests\Feature\Lms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class LmsAuthorizationTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_correct_portal_login(): void
    {
        $this->get(route('trainer.stream'))
            ->assertRedirect(route('login'));

        $this->get(route('trainee.stream'))
            ->assertRedirect(route('login'));

        $this->get(route('trainee.quizzes.index'))
            ->assertRedirect(route('login'));
    }

    public function test_trainer_and_trainee_lms_surfaces_are_role_isolated(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);

        $this->actingAs($trainee)
            ->get(route('trainer.stream'))
            ->assertForbidden();

        $this->actingAs($trainer)
            ->get(route('trainee.stream'))
            ->assertForbidden();

        $this->actingAs($trainer)
            ->get(route('trainee.quizzes.index'))
            ->assertForbidden();
    }
}
