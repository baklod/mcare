<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $this->actingAs($admin)
            ->get(route('admin.learning.alumni-jobs'))
            ->assertSee('data-dashboard-nav-key="admin-career-hub"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_non_admin_cannot_open_admin_learning_destinations(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);

        $this->actingAs($trainee)
            ->get(route('admin.learning.trainees'))
            ->assertForbidden();
    }

    public function test_large_admin_creation_forms_are_hidden_in_native_dialogs(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'trainee',
            'avatar_url' => 'https://example.test/managed-trainee.jpg',
        ]);
        $photoTrainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Photo Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $photoApplication = $this->approvedApplication($photoTrainee, $batch);
        $photoPath = "enrollment-documents/{$photoTrainee->id}/id-photo.png";
        Storage::disk('local')->put($photoPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZkN0AAAAASUVORK5CYII='));
        $photoApplication->update(['id_photo_path' => $photoPath]);
        $photoUrl = route('admin.accounts.photo', $photoTrainee, absolute: false);

        $this->actingAs($admin)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee('data-dashboard-dialog-open="trainer-account-dialog"', false)
            ->assertSee('<dialog id="trainer-account-dialog"', false)
            ->assertSee('data-dashboard-dialog-open="trainee-account-dialog"', false)
            ->assertSee('<dialog id="trainee-account-dialog"', false)
            ->assertSee('https://example.test/managed-trainee.jpg', false)
            ->assertSee($photoUrl, false);

        $this->actingAs($admin)
            ->get(route('admin.accounts.photo', $photoTrainee))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($photoTrainee)
            ->get(route('admin.accounts.photo', $photoTrainee))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.learning.alumni-jobs'))
            ->assertOk()
            ->assertSee('data-dashboard-dialog-open="career-opportunity-dialog"', false)
            ->assertSee('<dialog id="career-opportunity-dialog"', false);

        $this->actingAs($admin)
            ->get(route('admin.learning.modules'))
            ->assertOk()
            ->assertSee('Add a learning module')
            ->assertDontSee('Admin action');
    }

    public function test_admin_creation_validation_uses_the_matching_dialog_error_bag(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.accounts.trainers.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password'], null, 'trainer');

        $this->actingAs($admin)
            ->post(route('admin.learning.alumni-jobs.store'), [])
            ->assertSessionHasErrors([
                'estimated_start_date',
                'patient_gender',
                'mobility_status',
            ], null, 'careerCreate');
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

    public function test_graduation_unlocks_career_hub_on_the_same_trainee_account_and_a_correction_locks_it_again(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Alumni Transition',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);

        $this->actingAs($admin)
            ->patch(route('admin.learning.trainees.status', $application), [
                'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('users', [
            'id' => $trainee->id,
            'role' => 'trainee',
        ]);
        $this->actingAs($trainee->fresh())
            ->get(route('alumni.dashboard'))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.learning.trainees.status', $application->fresh()), [
                'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
                'learning_status_notes' => 'Graduation was recorded in error.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $trainee->id,
            'role' => 'trainee',
        ]);
        $this->actingAs($trainee->fresh())
            ->get(route('alumni.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_trainee_roster_shows_expandable_payment_module_and_assessment_summary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $traineeUser = User::factory()->create(['role' => 'trainee']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Summary',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($traineeUser, $batch);
        $application->update([
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'payment_amount' => 2000,
            'payment_currency' => 'PHP',
            'payment_reference' => 'MCARE-SUMMARY-001',
            'payment_verified_at' => now(),
        ]);
        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Completed Summary Module',
            'description' => 'Used to verify the admin trainee summary.',
            'file_path' => 'training-modules/summary.pdf',
            'original_file_name' => 'summary.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_published' => true,
            'published_at' => now(),
        ]);
        ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'last_viewed_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.trainees'))
            ->assertOk()
            ->assertSee('data-trainee-card', false)
            ->assertDontSee('data-dashboard-sidebar-collapse', false)
            ->assertDontSee('data-dashboard-menu-open', false)
            ->assertSee('Online payment')
            ->assertSee('1 of 1 published modules')
            ->assertSee('Ready for trainer assessment')
            ->assertSee('Assessment result: Not recorded yet');
    }

    public function test_admin_can_add_and_remove_a_training_module(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 10',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($admin)->post(route('admin.learning.modules.store'), [
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'module_code' => 'HCS323302',
            'title' => 'Provide Care and Support to Children',
            'topic' => 'Bathe and dress children',
            'description' => 'A module uploaded by the administrator.',
            'module_file' => UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf'),
            'is_published' => '1',
        ])->assertRedirect()->assertSessionHas('saved');

        $module = TrainingModule::query()->where('title', 'Provide Care and Support to Children')->firstOrFail();
        $this->assertEquals('HCS323302', $module->module_code);
        $this->assertEquals('Bathe and dress children', $module->topic);
        Storage::disk('local')->assertExists($module->file_path);

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module))
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('training_modules', ['id' => $module->id]);
        Storage::disk('local')->assertMissing($module->file_path);
    }

    public function test_admin_can_create_trainer_and_approved_trainee_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 11',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($admin)->post(route('admin.accounts.trainers.store'), [
            'name' => 'New Trainer',
            'email' => 'new.trainer@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect()->assertSessionHas('saved');

        $this->actingAs($admin)->post(route('admin.accounts.trainees.store'), [
            'first_name' => 'New',
            'middle_name' => 'M',
            'last_name' => 'Trainee',
            'email' => 'new.trainee@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'training_batch_id' => $batch->id,
            'birth_date' => '2001-01-01',
            'gender' => 'Female',
            'contact_number' => '09170001111',
            'schedule_preference' => 'AM',
            'street' => '11 Training Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2023,
        ])->assertRedirect()->assertSessionHas('saved');

        $this->assertDatabaseHas('users', ['email' => 'new.trainer@example.test', 'role' => 'trainer']);
        $this->assertDatabaseHas('users', ['email' => 'new.trainee@example.test', 'role' => 'trainee']);
        $this->assertDatabaseHas('enrollment_applications', [
            'email' => 'new.trainee@example.test',
            'training_batch_id' => $batch->id,
            'status' => EnrollmentApplication::STATUS_APPROVED,
        ]);
    }

    public function test_admin_can_export_the_filtered_trainee_roster_for_excel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Export',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
        $application = $this->approvedApplication($trainee, $batch);

        $response = $this->actingAs($admin)->get(route('admin.learning.trainees.export', [
            'batch_id' => $batch->id,
            'schedule' => 'AM',
        ]));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($application->email, $response->streamedContent());
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
