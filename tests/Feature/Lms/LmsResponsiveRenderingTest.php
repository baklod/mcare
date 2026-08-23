<?php

namespace Tests\Feature\Lms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class LmsResponsiveRenderingTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_trainer_lms_pages_expose_stable_mobile_safe_structure(): void
    {
        $trainer = $this->lmsUser('trainer');

        $this->actingAs($trainer)
            ->get(route('trainer.stream'))
            ->assertOk()
            ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1.0">', false)
            ->assertSee('data-lms-stream', false)
            ->assertSee('data-lms-role="trainer"', false)
            ->assertSee('data-announcement-composer', false)
            ->assertSee('data-dashboard-mobile-menu-open="dashboard-mobile-menu-trainer"', false)
            ->assertSee('Teaching Day')
            ->assertSee('Competency Records')
            ->assertSee('Certificates')
            ->assertSee('Reports');

        $this->actingAs($trainer)
            ->get(route('trainer.resources'))
            ->assertOk()
            ->assertSee('data-lms-classwork', false)
            ->assertSee('data-module-composer', false);

        $this->actingAs($trainer)
            ->get(route('trainer.assessments'))
            ->assertRedirect(route('trainer.resources'));
    }

    public function test_trainee_stream_and_quiz_pages_expose_mobile_safe_structure(): void
    {
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);

        $this->actingAs($trainee)
            ->get(route('trainee.stream'))
            ->assertOk()
            ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1.0">', false)
            ->assertSee('data-lms-stream', false)
            ->assertSee('data-lms-role="trainee"', false)
            ->assertSee('data-dashboard-mobile-menu-open="dashboard-mobile-menu-trainee"', false)
            ->assertSee('Home')
            ->assertSee('Payments')
            ->assertSee('Documents');

        $this->actingAs($trainee)
            ->get(route('trainee.quizzes.index'))
            ->assertRedirect(route('trainee.modules.index'));
    }
}
