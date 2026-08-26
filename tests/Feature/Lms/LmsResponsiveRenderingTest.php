<?php

namespace Tests\Feature\Lms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class LmsResponsiveRenderingTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_shared_dialog_backdrop_handler_ignores_clicks_from_file_controls(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('if (event.target !== dialog) return;', $script);
    }

    public function test_trainer_lms_pages_expose_stable_mobile_safe_structure(): void
    {
        $trainer = $this->lmsUser('trainer');
        $trainer->forceFill(['avatar_url' => 'https://example.test/trainer-avatar.jpg'])->save();

        $this->actingAs($trainer)
            ->get(route('trainer.stream'))
            ->assertOk()
            ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1.0">', false)
            ->assertSee('data-lms-stream', false)
            ->assertSee('data-lms-role="trainer"', false)
            ->assertSee('data-announcement-composer', false)
            ->assertSee('https://example.test/trainer-avatar.jpg', false)
            ->assertSee('data-dashboard-mobile-menu-open="dashboard-mobile-menu-trainer"', false)
            ->assertSee('Teaching Day')
            ->assertSee('Competency Records')
            ->assertSee('Certificates')
            ->assertSee('Reports');

        $this->actingAs($trainer)
            ->get(route('trainer.resources'))
            ->assertOk()
            ->assertSee('data-lms-classwork', false)
            ->assertSee('id="module-creator-dialog"', false)
            ->assertSee('id="quiz-creator-dialog"', false)
            ->assertSee('data-dashboard-dialog', false)
            ->assertDontSee('data-classwork-tab="all"', false)
            ->assertDontSee('Attached Assessments');

        $this->actingAs($trainer)
            ->get(route('trainer.assessments'))
            ->assertRedirect(route('trainer.resources'));
    }

    public function test_trainee_stream_and_quiz_pages_expose_mobile_safe_structure(): void
    {
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $trainee->forceFill(['avatar_url' => 'https://example.test/trainee-avatar.jpg'])->save();

        $this->actingAs($trainee)
            ->get(route('trainee.stream'))
            ->assertOk()
            ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1.0">', false)
            ->assertSee('data-lms-stream', false)
            ->assertSee('data-lms-role="trainee"', false)
            ->assertSee('data-dashboard-mobile-menu-open="dashboard-mobile-menu-trainee"', false)
            ->assertSee('https://example.test/trainee-avatar.jpg', false)
            ->assertSee('Home')
            ->assertSee('Payments')
            ->assertSee('Documents');

        $this->actingAs($trainee)
            ->get(route('trainee.quizzes.index'))
            ->assertRedirect(route('trainee.modules.index'));
    }

    public function test_module_and_roster_surfaces_render_google_account_avatars_for_both_roles(): void
    {
        $trainer = $this->lmsUser('trainer');
        $trainer->forceFill(['avatar_url' => 'https://example.test/module-trainer-avatar.jpg'])->save();
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $trainee->forceFill(['avatar_url' => 'https://example.test/module-trainee-avatar.jpg'])->save();
        $module = $this->lmsModule($trainer, $batch);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.index'))
            ->assertOk()
            ->assertSee('https://example.test/module-trainer-avatar.jpg', false);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('https://example.test/module-trainer-avatar.jpg', false);

        $this->actingAs($trainer)
            ->get(route('trainer.modules.show', $module))
            ->assertOk()
            ->assertSee('https://example.test/module-trainee-avatar.jpg', false)
            ->assertSee('data-module-file-preview', false)
            ->assertSee('data-pdf-fit-mode="page"', false);

        $this->actingAs($trainer)
            ->get(route('trainer.trainees'))
            ->assertOk()
            ->assertSee('https://example.test/module-trainee-avatar.jpg', false);
    }
}
