<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchScheduleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_new_active_batch_keeps_the_previous_batch_active(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([
            ['name' => 'August Batch 1', 'deadline' => now()->addMonth()],
            ['name' => 'September Batch 1', 'deadline' => now()->addMonths(2)],
        ] as $batch) {
            $this->actingAs($admin)
                ->post(route('admin.schedules.store'), [
                    'name' => $batch['name'],
                    'year' => 2026,
                    'is_active' => '1',
                    'enrollment_ends_at' => $batch['deadline']->toDateTimeString(),
                    'am_days' => 'MWF',
                    'pm_days' => 'TTS',
                ])
                ->assertRedirect(route('admin.schedules.index'));
        }

        $this->assertDatabaseHas('training_batches', [
            'name' => 'August Batch 1',
            'year' => 2026,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('training_batches', [
            'name' => 'September Batch 1',
            'year' => 2026,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.schedules.index'))
            ->assertSee('August Batch 1 2026')
            ->assertSee('September Batch 1 2026');
    }

    public function test_admin_can_assign_trainer_to_batches_freely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create([
            'role' => 'trainer',
            'email' => 'trainer@gmail.com',
        ]);
        $existingBatch = TrainingBatch::create([
            'name' => 'August Batch 1',
            'year' => 2026,
            'trainer_id' => $trainer->id,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'pm_days' => 'TTS',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.schedules.store'), [
                'name' => 'September Batch 1',
                'year' => 2026,
                'trainer_id' => $trainer->id,
                'is_active' => '1',
                'enrollment_ends_at' => now()->addMonths(2)->toDateTimeString(),
                'am_days' => 'MWF',
                'pm_days' => 'TTS',
            ])
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('training_batches', [
            'name' => 'September Batch 1',
            'year' => 2026,
            'trainer_id' => $trainer->id,
        ]);
        $this->assertDatabaseHas('training_batches', [
            'id' => $existingBatch->id,
            'trainer_id' => $trainer->id,
        ]);
    }

    public function test_admin_can_edit_batch_to_assign_trainer_with_existing_times(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'trainer_id' => null,
            'is_active' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addMonth(),
            'training_starts_at' => now()->addMonth(),
            'training_ends_at' => now()->addMonths(3),
            'am_days' => 'MWF',
            'am_start_time' => '08:00:00',
            'am_end_time' => '12:00:00',
            'am_room' => 'Skills Lab',
            'pm_days' => 'TTH',
            'pm_start_time' => '13:00:00',
            'pm_end_time' => '17:00:00',
            'pm_room' => 'Lecture Room',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.schedules.update', $batch), [
                'name' => 'Batch 1',
                'year' => 2026,
                'trainer_id' => $trainer->id,
                'is_active' => '1',
                'enrollment_starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
                'enrollment_ends_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'training_starts_at' => now()->addMonth()->format('Y-m-d\TH:i'),
                'training_ends_at' => now()->addMonths(3)->format('Y-m-d\TH:i'),
                'am_days' => 'MWF',
                'am_start_time' => '08:00:00',
                'am_end_time' => '12:00:00',
                'am_room' => 'Skills Lab',
                'pm_days' => 'TTH',
                'pm_start_time' => '13:00:00',
                'pm_end_time' => '17:00:00',
                'pm_room' => 'Lecture Room',
            ])
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('training_batches', [
            'id' => $batch->id,
            'trainer_id' => $trainer->id,
        ]);
    }

    public function test_admin_can_edit_a_batch_after_its_enrollment_window_closed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'August Batch 1',
            'year' => 2026,
            'trainer_id' => $trainer->id,
            'is_active' => false,
            'enrollment_starts_at' => now()->subMonths(2),
            'enrollment_ends_at' => now()->subMonth(),
            'training_starts_at' => now()->subWeeks(5),
            'training_ends_at' => now()->addMonths(2),
            'am_days' => 'MWF',
            'pm_days' => 'TTS',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.schedules.update', $batch), [
                'name' => 'August Batch 1',
                'year' => 2026,
                'trainer_id' => $trainer->id,
                'is_active' => '0',
                'enrollment_starts_at' => now()->subMonths(2)->toDateTimeString(),
                'enrollment_ends_at' => now()->subMonth()->toDateTimeString(),
                'training_starts_at' => now()->subWeeks(5)->toDateTimeString(),
                'training_ends_at' => now()->addMonths(2)->toDateTimeString(),
                'am_days' => 'MWF',
                'am_start_time' => '08:30',
                'am_end_time' => '12:00',
                'am_room' => 'Updated Skills Lab',
                'pm_days' => 'TTS',
                'pm_start_time' => '13:00',
                'pm_end_time' => '17:00',
                'pm_room' => 'Updated Lecture Room',
                'notes' => 'Schedule updated after enrollment closed.',
            ])
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('training_batches', [
            'id' => $batch->id,
            'am_room' => 'Updated Skills Lab',
            'pm_room' => 'Updated Lecture Room',
        ]);
    }

    public function test_trainer_catalog_keeps_batch_history_but_roster_is_scoped_to_assignment(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $assignedBatch = TrainingBatch::create([
            'name' => 'August Batch 1',
            'year' => 2026,
            'trainer_id' => $trainer->id,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'pm_days' => 'TTS',
        ]);
        $otherBatch = TrainingBatch::create([
            'name' => 'September Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonths(2),
            'am_days' => 'MWF',
            'pm_days' => 'TTS',
        ]);
        $assignedTrainee = User::factory()->create(['role' => 'trainee']);
        $otherTrainee = User::factory()->create(['role' => 'trainee']);

        $this->approvedApplication($assignedTrainee, $assignedBatch, 'Assigned Learner');
        $this->approvedApplication($otherTrainee, $otherBatch, 'Other Learner');

        $this->actingAs($trainer)
            ->get(route('trainer.trainings'))
            ->assertOk()
            ->assertSee('August Batch 1 2026')
            ->assertSee('September Batch 1 2026')
            ->assertSee('Assigned to you');

        $this->actingAs($trainer)
            ->get(route('trainer.trainees'))
            ->assertOk()
            ->assertSee('Assigned Learner')
            ->assertDontSee('Other Learner');
    }

    private function approvedApplication(User $user, TrainingBatch $batch, string $firstName): EnrollmentApplication
    {
        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => $firstName,
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
        ]);
    }
}
