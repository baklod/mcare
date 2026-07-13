<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TraineePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_trainee_login(): void
    {
        $this->get('/trainee')
            ->assertRedirect(route('trainee.login'));
    }

    public function test_non_trainee_cannot_open_trainee_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($user)
            ->get('/trainee')
            ->assertForbidden();
    }

    public function test_admin_approval_promotes_applicant_to_trainee_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->approvedReadyApplication($applicant, EnrollmentApplication::STATUS_PRE_ENLISTMENT);

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'admin_notes' => 'Approved for Batch 1.',
            ])
            ->assertRedirect(route('admin.enrollments.show', $application));

        $this->assertDatabaseHas('users', [
            'id' => $applicant->id,
            'role' => 'trainee',
            'applicant_status' => EnrollmentApplication::STATUS_APPROVED,
        ]);
    }

    public function test_approved_trainee_can_open_dashboard(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = $this->batch();
        $this->approvedReadyApplication($trainee, EnrollmentApplication::STATUS_APPROVED, $batch);

        TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Infection Control',
            'description' => 'Core caregiving safety lesson.',
            'file_path' => 'training-modules/sample.pdf',
            'original_file_name' => 'sample.pdf',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Infection Control')
            ->assertSee('MWF | 8:00 AM - 12:00 PM');
    }

    public function test_private_module_is_visible_only_to_its_selected_trainee(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $targetUser = User::factory()->create(['role' => 'trainee']);
        $otherUser = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $targetApplication = $this->approvedReadyApplication($targetUser, EnrollmentApplication::STATUS_APPROVED, $batch);
        $this->approvedReadyApplication($otherUser, EnrollmentApplication::STATUS_APPROVED, $batch);

        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'target_enrollment_application_id' => $targetApplication->id,
            'title' => 'Private Coaching Module',
            'description' => 'Targeted learner follow-up.',
            'file_path' => 'training-modules/private.pdf',
            'original_file_name' => 'private.pdf',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($targetUser)
            ->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertSee('Private Coaching Module');

        $this->actingAs($otherUser)
            ->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertDontSee('Private Coaching Module');

        $this->actingAs($otherUser)
            ->get(route('trainee.modules.content', $module))
            ->assertForbidden();
    }

    public function test_module_viewing_and_completion_are_recorded_on_the_server(): void
    {
        Storage::fake('local');
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $application = $this->approvedReadyApplication($trainee, EnrollmentApplication::STATUS_APPROVED, $batch);
        Storage::disk('local')->put('training-modules/lesson.pdf', '%PDF-1.4 test');
        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Protected PDF Lesson',
            'description' => 'Tracked learning material.',
            'file_path' => 'training-modules/lesson.pdf',
            'original_file_name' => 'lesson.pdf',
            'mime_type' => 'application/pdf',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('Protected learning viewer');

        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => 'in_progress',
            'progress_percent' => 10,
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.content', $module))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename=lesson.pdf')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.progress', $module), ['action' => 'complete'])
            ->assertRedirect();

        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => 'completed',
            'progress_percent' => 100,
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $trainee->id,
            'action' => 'trainee.module.progress.updated',
        ]);
    }

    private function batch(): TrainingBatch
    {
        return TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'am_start_time' => '08:00',
            'am_end_time' => '12:00',
            'am_room' => 'Skills Lab A',
            'pm_days' => 'TTS',
            'pm_start_time' => '13:00',
            'pm_end_time' => '17:00',
            'pm_room' => 'Lecture Room 2',
        ]);
    }

    private function approvedReadyApplication(User $user, string $status, ?TrainingBatch $batch = null): EnrollmentApplication
    {
        $batch ??= $this->batch();

        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => $status,
        ]);
    }
}
