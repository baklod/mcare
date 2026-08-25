<?php

namespace Tests\Feature\Lms;

use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class RollingEnrollmentModuleReleaseTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_latest_continuous_batch_accepts_enrollment_without_a_deadline(): void
    {
        $this->lmsBatch([
            'name' => 'Older Continuous Batch',
            'training_starts_at' => now()->subMonth(),
            'is_continuous_enrollment' => true,
            'enrollment_ends_at' => null,
        ]);
        $latest = $this->lmsBatch([
            'name' => 'Latest Continuous Batch',
            'training_starts_at' => now()->subWeek(),
            'is_continuous_enrollment' => true,
            'enrollment_ends_at' => null,
        ]);

        $this->assertTrue($latest->acceptsEnrollment());
        $this->assertSame('continuous', $latest->enrollmentState());
        $this->assertTrue(TrainingBatch::openForEnrollment()?->is($latest));
    }

    public function test_late_enrollee_is_assigned_only_the_current_active_module(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch([
            'is_continuous_enrollment' => true,
            'enrollment_ends_at' => null,
        ]);
        ['user' => $existingUser, 'application' => $existingApplication] = $this->lmsTrainee($batch);

        $firstModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-001',
            'title' => 'Previously Released Core Module',
        ]);

        $this->actingAs($existingUser)
            ->patch(route('trainee.modules.progress', $firstModule), ['action' => 'submit'])
            ->assertSessionHasNoErrors();

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $firstModule), [
                'enrollment_application_id' => $existingApplication->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $currentModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-002',
            'title' => 'Current Active Core Module',
        ]);

        $this->assertSame(TrainingModule::DELIVERY_CLOSED, $firstModule->fresh()->delivery_status);
        $this->assertSame(TrainingModule::DELIVERY_ACTIVE, $currentModule->fresh()->delivery_status);

        ['user' => $lateUser, 'application' => $lateApplication] = $this->lmsTrainee($batch);

        $this->assertDatabaseMissing('module_progress', [
            'enrollment_application_id' => $lateApplication->id,
            'training_module_id' => $firstModule->id,
        ]);
        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $lateApplication->id,
            'training_module_id' => $currentModule->id,
            'sequence_number' => 1,
            'status' => ModuleProgress::STATUS_NOT_STARTED,
        ]);

        $visibleIds = TrainingModule::query()
            ->availableTo($lateApplication)
            ->pluck('id');

        $this->assertFalse($visibleIds->contains($firstModule->id));
        $this->assertTrue($visibleIds->contains($currentModule->id));
        $this->actingAs($lateUser)
            ->get(route('trainee.modules.show', $firstModule))
            ->assertNotFound();
        $this->actingAs($lateUser)
            ->get(route('trainee.modules.show', $currentModule))
            ->assertOk();
    }

    public function test_next_module_unlocks_only_after_mark_as_done_and_competent_validation(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['is_continuous_enrollment' => true]);
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $firstModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-001',
            'title' => 'First Assigned Module',
        ]);
        $nextModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-002',
            'title' => 'Locked Next Module',
        ]);

        $nextProgress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $nextModule->id)
            ->firstOrFail();
        $this->assertSame(ModuleProgress::STATUS_LOCKED, $nextProgress->status);
        $this->assertNull($nextProgress->unlocked_at);
        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $nextModule))
            ->assertNotFound();

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.progress', $firstModule), ['action' => 'submit'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ModuleProgress::STATUS_AWAITING_EVALUATION,
            $this->progressFor($application->id, $firstModule->id)->status,
        );
        $this->assertSame(ModuleProgress::STATUS_LOCKED, $nextProgress->fresh()->status);

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $firstModule), [
                'enrollment_application_id' => $application->id,
                'competency_outcome' => ModuleProgress::OUTCOME_NOT_YET_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ModuleProgress::STATUS_NEEDS_REMEDIATION,
            $this->progressFor($application->id, $firstModule->id)->status,
        );
        $this->assertNull($this->progressFor($application->id, $firstModule->id)->submitted_at);
        $this->assertSame(ModuleProgress::STATUS_LOCKED, $nextProgress->fresh()->status);

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $firstModule), [
                'enrollment_application_id' => $application->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasErrors('competency_outcome');
        $this->assertSame(ModuleProgress::STATUS_LOCKED, $nextProgress->fresh()->status);

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.progress', $firstModule), ['action' => 'submit'])
            ->assertSessionHasNoErrors();
        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $firstModule), [
                'enrollment_application_id' => $application->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $nextProgress->refresh();
        $this->assertSame(ModuleProgress::STATUS_NOT_STARTED, $nextProgress->status);
        $this->assertNotNull($nextProgress->unlocked_at);
        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $nextModule))
            ->assertOk();
    }

    public function test_mark_as_done_requires_every_released_module_quiz_to_be_passed(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        $quiz = Quiz::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'title' => 'Required Module Check',
            'instructions' => 'Pass before marking the module as done.',
            'is_published' => true,
            'published_at' => now(),
            'attempt_limit' => 3,
            'passing_score_percent' => 75,
        ]);

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.progress', $module), ['action' => 'submit'])
            ->assertSessionHasErrors('action');

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_GRADED,
            'earned_points' => 8,
            'total_points' => 10,
            'score_percent' => 80,
            'passed' => true,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'graded_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.progress', $module), ['action' => 'submit'])
            ->assertSessionHasNoErrors();

        $progress = $this->progressFor($application->id, $module->id);
        $this->assertSame(ModuleProgress::STATUS_AWAITING_EVALUATION, $progress->status);
        $this->assertNotNull($progress->submitted_at);
        $this->assertNull($progress->completed_at);
    }

    private function progressFor(int $applicationId, int $moduleId): ModuleProgress
    {
        return ModuleProgress::query()
            ->where('enrollment_application_id', $applicationId)
            ->where('training_module_id', $moduleId)
            ->firstOrFail();
    }
}
