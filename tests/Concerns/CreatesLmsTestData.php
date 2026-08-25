<?php

namespace Tests\Concerns;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\RollingModuleReleaseService;

trait CreatesLmsTestData
{
    protected function lmsUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    protected function lmsBatch(array $overrides = []): TrainingBatch
    {
        return TrainingBatch::create(array_merge([
            'name' => 'Caregiving Batch A',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'training_starts_at' => now()->subWeek(),
            'training_ends_at' => now()->addMonths(2),
            'am_days' => 'MWF',
            'am_start_time' => '08:00',
            'am_end_time' => '12:00',
            'am_room' => 'Skills Lab A',
            'pm_days' => 'TTS',
            'pm_start_time' => '13:00',
            'pm_end_time' => '17:00',
            'pm_room' => 'Lecture Room B',
        ], $overrides));
    }

    /**
     * @return array{user: User, application: EnrollmentApplication}
     */
    protected function lmsTrainee(TrainingBatch $batch, array $overrides = []): array
    {
        $user = $this->lmsUser('trainee');

        $application = EnrollmentApplication::create(array_merge([
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
            'learning_started_at' => now(),
        ], $overrides));

        app(RollingModuleReleaseService::class)->assignCurrentTo($application);

        return compact('user', 'application');
    }

    protected function lmsModule(
        User $trainer,
        TrainingBatch $batch,
        array $overrides = [],
    ): TrainingModule {
        $module = TrainingModule::create(array_merge([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Safe Patient Transfer',
            'description' => 'A browser-viewable Caregiving NC II learning module.',
            'file_path' => "training-modules/{$trainer->id}/patient-transfer.pdf",
            'original_file_name' => 'patient-transfer.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_published' => true,
            'published_at' => now(),
        ], $overrides));

        if ($module->is_published) {
            app(RollingModuleReleaseService::class)->activate($module);
        }

        return $module;
    }
}
