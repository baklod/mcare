<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\TrainerAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class TrainerSearchTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_guest_is_redirected_from_trainer_search(): void
    {
        $this->get(route('trainer.search', ['q' => 'people']))
            ->assertRedirect(route('login'));
    }

    public function test_non_trainer_cannot_search_the_trainer_portal(): void
    {
        $user = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($user)
            ->get(route('trainer.search', ['q' => 'people']))
            ->assertForbidden();
    }

    public function test_trainer_search_box_is_on_every_trainer_page(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->actingAs($trainer)
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('data-trainer-global-search', false)
            ->assertSee('Search pages, people, modules...');
    }

    public function test_trainer_can_search_pages_people_modules_and_quizzes(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id, 'name' => 'Nightingale Cohort']);
        $this->lmsTrainee($batch, ['first_name' => 'Liza', 'last_name' => 'Dela Cruz', 'email' => 'liza.delacruz@gmail.com']);
        $module = $this->lmsModule($trainer, $batch, [
            'title' => 'Provide Care and Support to Infants',
            'module_code' => 'HCS323301',
        ]);
        Quiz::query()->create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'title' => 'Infant Comfort Skills Quiz',
            'instructions' => 'Answer the infant care items.',
            'is_published' => true,
            'published_at' => now(),
            'attempt_limit' => 1,
            'passing_score_percent' => 75,
        ]);
        TrainerAnnouncement::query()->create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Bring your skills kit',
            'message' => 'Wear closed shoes for the lab demonstration.',
            'audience' => 'trainees',
            'kind' => TrainerAnnouncement::KIND_REMINDER,
            'is_published' => true,
            'posted_at' => now(),
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.search', ['q' => 'People']))
            ->assertOk()
            ->assertSee('Approved learner roster')
            ->assertSee(route('trainer.trainees', absolute: false), false);

        $this->actingAs($trainer)
            ->get(route('trainer.search', ['q' => 'Liza Dela']))
            ->assertOk()
            ->assertSee('Dela Cruz, Liza')
            ->assertSee('liza.delacruz@gmail.com');

        $this->actingAs($trainer)
            ->get(route('trainer.search', ['q' => 'HCS323301']))
            ->assertOk()
            ->assertSee('Provide Care and Support to Infants')
            ->assertSee(route('trainer.modules.show', $module, absolute: false), false);

        $this->actingAs($trainer)
            ->get(route('trainer.search', ['q' => 'Infant Comfort']))
            ->assertOk()
            ->assertSee('Infant Comfort Skills Quiz');

        $this->actingAs($trainer)
            ->get(route('trainer.search', ['q' => 'Nightingale']))
            ->assertOk()
            ->assertSee('Nightingale Cohort');

        $this->actingAs($trainer)
            ->get(route('trainer.search', ['q' => 'skills kit']))
            ->assertOk()
            ->assertSee('Bring your skills kit');

        $this->actingAs($trainer)
            ->get(route('trainer.search.suggest', ['q' => 'Classwork']))
            ->assertOk()
            ->assertJsonPath('groups.0.label', 'Pages')
            ->assertJsonFragment(['title' => 'Classwork']);
    }

    public function test_trainer_search_stays_inside_the_assigned_class(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $otherTrainer = User::factory()->create(['role' => 'trainer']);
        $this->lmsBatch(['trainer_id' => $trainer->id, 'name' => 'Assigned Cohort']);
        $foreignBatch = $this->lmsBatch([
            'trainer_id' => $otherTrainer->id,
            'name' => 'Foreign Cohort',
            'is_active' => true,
        ]);
        $this->lmsTrainee($foreignBatch, [
            'first_name' => 'Secret',
            'last_name' => 'Learner',
            'email' => 'secret.learner@gmail.com',
        ]);
        $this->lmsModule($otherTrainer, $foreignBatch, [
            'title' => 'Hidden Housekeeping Drill',
            'module_code' => 'MCARE-HIDDEN',
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.search', ['q' => 'Secret Learner']))
            ->assertOk()
            ->assertDontSee('secret.learner@gmail.com')
            ->assertDontSee('Learner, Secret')
            ->assertDontSee('Hidden Housekeeping Drill');
    }
}
