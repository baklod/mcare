<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
