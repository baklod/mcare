<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_trainer_login(): void
    {
        $this->get('/trainer')
            ->assertRedirect(route('trainer.login'));
    }

    public function test_non_trainer_cannot_open_trainer_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($user)
            ->get('/trainer')
            ->assertForbidden();
    }

    public function test_trainer_can_view_dashboard_with_active_trainees(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $batch = TrainingBatch::create([
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

        EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'training_batch_id' => $batch->id,
            'email' => 'trainee@gmail.com',
            'program' => 'Caregiving NC II',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
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
            'status' => EnrollmentApplication::STATUS_APPROVED,
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('Ana')
            ->assertSee('MWF | 8:00 AM - 12:00 PM');
    }

    public function test_trainer_can_upload_private_module(): void
    {
        Storage::fake('local');

        $trainer = User::factory()->create(['role' => 'trainer']);
        TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'pm_days' => 'TTS',
        ]);

        $file = UploadedFile::fake()->create('infection-control.pdf', 128, 'application/pdf');

        $this->actingAs($trainer)
            ->post(route('trainer.modules.store'), [
                'title' => '03 - Infection Control',
                'description' => 'Safe infection prevention lesson for Caregiving NC II trainees.',
                'module_file' => $file,
            ])
            ->assertRedirect(route('trainer.dashboard').'#modules');

        $this->assertDatabaseHas('training_modules', [
            'trainer_id' => $trainer->id,
            'title' => '03 - Infection Control',
            'original_file_name' => 'infection-control.pdf',
        ]);

        $path = Storage::disk('local')->files("training-modules/{$trainer->id}")[0] ?? null;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
    }
}
