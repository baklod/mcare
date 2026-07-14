<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLearningSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_learning_destinations_are_separate_and_accessible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([
            'admin.learning.trainees',
            'admin.learning.modules',
            'admin.learning.certificates',
            'admin.learning.alumni-jobs',
            'admin.learning.reports',
        ] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }
    }

    public function test_non_admin_cannot_open_admin_learning_destinations(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);

        $this->actingAs($trainee)
            ->get(route('admin.learning.trainees'))
            ->assertForbidden();
    }

    public function test_admin_can_filter_and_update_a_trainee_learning_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $traineeUser = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 8',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'training_starts_at' => now()->subWeek(),
            'training_ends_at' => now()->addMonths(3),
        ]);
        $application = $this->approvedApplication($traineeUser, $batch);

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees', [
                'batch_id' => $batch->id,
                'schedule' => 'AM',
                'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
                'training_state' => 'in_progress',
                'joined_from' => now()->subDay()->toDateString(),
                'joined_to' => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Trainee lifecycle records')
            ->assertSee('Pause')
            ->assertSee('Graduate')
            ->assertSee($application->email);

        $this->actingAs($admin)
            ->patch(route('admin.learning.trainees.status', $application), [
                'learning_status' => EnrollmentApplication::LEARNING_PAUSED,
                'learning_status_notes' => 'Paused while learner confirms availability.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('enrollment_applications', [
            'id' => $application->id,
            'learning_status' => EnrollmentApplication::LEARNING_PAUSED,
            'learning_status_changed_by_id' => $admin->id,
        ]);
        $this->assertTrue(AdminActivityLog::query()
            ->where('action', 'trainee.learning-status.updated')
            ->where('subject_id', $application->id)
            ->exists());

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees', ['learning_status' => EnrollmentApplication::LEARNING_PAUSED]))
            ->assertOk()
            ->assertSee($application->email)
            ->assertSee('Resume');
    }

    public function test_non_admin_cannot_change_a_trainee_learning_status(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 9',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);

        $this->actingAs($trainee)
            ->patch(route('admin.learning.trainees.status', $application), [
                'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            ])
            ->assertForbidden();
    }

    private function approvedApplication(User $user, TrainingBatch $batch): EnrollmentApplication
    {
        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Lifecycle',
            'last_name' => 'Trainee',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
            'reviewed_at' => now(),
        ]);
    }
}
