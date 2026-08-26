<?php

namespace Tests\Feature\Lms;

use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TrainingModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class GraduateGradesTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_graduate_sees_only_trainer_evaluated_grades_and_cannot_read_uploaded_modules(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $graduate, 'application' => $application] = $this->lmsTrainee($batch);
        $competentModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CG-NCII-001',
            'title' => 'Validated Safe Patient Transfer',
            'position' => 1,
        ]);
        $notYetCompetentModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CG-NCII-002',
            'title' => 'Validated Medication Support',
            'position' => 2,
        ]);
        $unevaluatedModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CG-NCII-003',
            'title' => 'Unevaluated Current Module',
            'position' => 3,
        ]);

        $this->progressFor($application, $competentModule)->forceFill([
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'quiz_score' => 92.5,
            'practical_rating' => ModuleProgress::RATING_COMPETENT,
            'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            'evaluation_remarks' => 'Demonstrated the required transfer procedure.',
            'evaluated_by_id' => $trainer->id,
            'evaluated_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2),
        ])->save();

        $this->progressFor($application, $notYetCompetentModule)->forceFill([
            'status' => ModuleProgress::STATUS_NEEDS_REMEDIATION,
            'progress_percent' => 99,
            'quiz_score' => 68,
            'practical_rating' => ModuleProgress::RATING_NOT_YET_COMPETENT,
            'competency_outcome' => ModuleProgress::OUTCOME_NOT_YET_COMPETENT,
            'evaluation_remarks' => 'Repeat the medication-safety demonstration.',
            'evaluated_by_id' => $trainer->id,
            'evaluated_at' => now()->subDay(),
        ])->save();

        $application->forceFill([
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ])->save();

        $this->actingAs($graduate)
            ->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertSee('Course grades')
            ->assertSee('Caregiving NC II')
            ->assertSee('2 trainer-validated grades')
            ->assertSee('View Grades');

        $this->actingAs($graduate)
            ->get(route('trainee.grades'))
            ->assertOk()
            ->assertSee('Validated module grades')
            ->assertSee('Validated Safe Patient Transfer')
            ->assertSee('92.5%')
            ->assertSee('Competent (Passed)')
            ->assertSee('Validated Medication Support')
            ->assertSee('68.0%')
            ->assertSee('For Remediation')
            ->assertSee($trainer->name)
            ->assertDontSee('Unevaluated Current Module')
            ->assertDontSee('Open Module');

        foreach (['index', 'show', 'content', 'download'] as $route) {
            $parameters = $route === 'index' ? [] : [$competentModule];

            $this->actingAs($graduate)
                ->get(route("trainee.modules.{$route}", $parameters))
                ->assertForbidden();
        }

        $this->actingAs($graduate)
            ->get(route('trainee.quizzes.index'))
            ->assertForbidden();

        $this->actingAs($graduate)
            ->post(route('classroom-comments.store'), [
                'commentable_type' => 'module',
                'commentable_id' => $competentModule->id,
                'visibility' => 'class',
                'body' => 'Graduates cannot post to closed classwork.',
            ])
            ->assertForbidden();

        $this->assertNull($this->progressFor($application, $unevaluatedModule)->evaluated_at);
    }

    public function test_early_graduate_sees_an_empty_grade_record_until_a_trainer_evaluates_a_module(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $graduate, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch, [
            'title' => 'Module Without Trainer Evaluation',
        ]);

        $application->forceFill([
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ])->save();

        $this->actingAs($graduate)
            ->get(route('trainee.grades'))
            ->assertOk()
            ->assertSee('No trainer-validated grades yet')
            ->assertSee('No trainer-validated grades have been recorded for this course.')
            ->assertDontSee($module->title);
    }

    public function test_active_trainee_keeps_classwork_access_but_cannot_open_graduate_grades(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk();

        $this->actingAs($trainee)
            ->get(route('trainee.grades'))
            ->assertForbidden();
    }

    private function progressFor(EnrollmentApplication $application, TrainingModule $module): ModuleProgress
    {
        return ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $module->id)
            ->firstOrFail();
    }
}
