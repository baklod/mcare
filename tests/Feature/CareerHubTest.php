<?php

namespace Tests\Feature;

use App\Models\CareerOpportunity;
use App\Models\EnrollmentApplication;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\CareerOpportunityPublished;
use App\Notifications\LmsAnnouncementPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_a_career_opportunity_and_notify_alumni(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $alumni = User::factory()->create(['role' => 'alumni']);

        $this->actingAs($admin)
            ->post(route('admin.learning.alumni-jobs.store'), [
                'title' => 'Home Caregiver',
                'employer' => 'MCARE Partner Home',
                'location' => 'Iriga City',
                'employment_type' => CareerOpportunity::TYPE_FULL_TIME,
                'description' => 'Provide respectful daily care under the employer care plan.',
                'requirements' => 'Caregiving NC II and a valid government ID.',
                'application_url' => 'https://example.test/careers/home-caregiver',
                'application_deadline' => now()->addMonth()->format('Y-m-d H:i:s'),
                'is_published' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved', 'Career opportunity published and alumni were notified.');

        $opportunity = CareerOpportunity::query()->firstOrFail();

        $this->assertDatabaseHas('career_opportunities', [
            'id' => $opportunity->id,
            'is_published' => true,
            'employer' => 'MCARE Partner Home',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $alumni->id,
            'type' => CareerOpportunityPublished::class,
        ]);

        $this->actingAs($alumni)
            ->get(route('alumni.dashboard'))
            ->assertOk()
            ->assertSee('Home Caregiver')
            ->assertSee('MCARE Partner Home');
    }

    public function test_alumni_cannot_see_a_career_draft_or_read_another_account_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $alumni = User::factory()->create(['role' => 'alumni']);
        $otherAlumni = User::factory()->create(['role' => 'alumni']);

        $this->actingAs($admin)->post(route('admin.learning.alumni-jobs.store'), [
            'title' => 'Published Caregiver',
            'employer' => 'Open Care',
            'description' => 'A published opportunity.',
            'is_published' => '1',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.learning.alumni-jobs.store'), [
            'title' => 'Draft Caregiver',
            'employer' => 'Pending Care',
            'description' => 'A listing waiting for confirmation.',
        ])->assertRedirect();

        $notification = $alumni->notifications()->firstOrFail();

        $this->actingAs($alumni)
            ->get(route('alumni.dashboard'))
            ->assertOk()
            ->assertSee('Published Caregiver')
            ->assertDontSee('Draft Caregiver');

        $this->actingAs($otherAlumni)
            ->patch(route('notifications.read', $notification))
            ->assertNotFound();

        $this->actingAs($alumni)
            ->patch(route('notifications.read', $notification))
            ->assertRedirect()
            ->assertSessionHas('saved', 'Notification marked as read.');

        $this->assertNotNull($alumni->notifications()->firstOrFail()->fresh()->read_at);
    }

    public function test_admin_can_preview_the_published_alumni_feed_without_impersonating_an_alumni(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);

        CareerOpportunity::create([
            'created_by_id' => $admin->id,
            'title' => 'Preview Caregiver Role',
            'employer' => 'MCARE Preview Partner',
            'description' => 'Published role visible in the admin preview.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.alumni-jobs.preview'))
            ->assertOk()
            ->assertSee('Admin preview of the alumni experience')
            ->assertSee('Preview Caregiver Role');

        $this->actingAs($trainee)
            ->get(route('admin.learning.alumni-jobs.preview'))
            ->assertForbidden();
    }

    public function test_published_batch_announcement_notifies_only_approved_trainees_in_that_batch(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $otherTrainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Notification',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        EnrollmentApplication::create([
            'user_id' => $trainee->id,
            'training_batch_id' => $batch->id,
            'email' => $trainee->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Approved',
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
        ]);

        $this->actingAs($trainer)
            ->post(route('trainer.announcements.store'), [
                'training_batch_id' => $batch->id,
                'kind' => TrainerAnnouncement::KIND_ANNOUNCEMENT,
                'audience' => 'trainees',
                'title' => 'Bring your assessment kit',
                'message' => 'Please bring your assessment kit to the next session.',
                'is_published' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $trainee->id,
            'type' => LmsAnnouncementPublished::class,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $otherTrainee->id,
            'type' => LmsAnnouncementPublished::class,
        ]);
    }
}
