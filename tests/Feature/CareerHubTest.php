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
                'estimated_start_date' => now()->addWeek()->toDateString(),
                'patient_gender' => CareerOpportunity::GENDER_FEMALE,
                'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
                'patient_age' => 72,
                'specific_contraptions' => 'Walker',
                'condition_summary' => 'Needs mobility support during daily routines.',
                // Unapproved fields are ignored even when a crafted request sends them.
                'medical_history' => 'Must never be stored.',
                'application_email' => 'private@example.test',
                'requirements' => 'Upload patient records.',
                'is_published' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved', 'Career opportunity published and alumni were notified.');

        $adminPage = $this->actingAs($admin)->get(route('admin.learning.alumni-jobs'));
        $adminPage
            ->assertOk()
            ->assertSee('data-auto-dismiss="5000"', false);
        $this->assertSame(1, substr_count(
            $adminPage->getContent(),
            'Career opportunity published and alumni were notified.'
        ));

        $opportunity = CareerOpportunity::query()->firstOrFail();

        $this->assertDatabaseHas('career_opportunities', [
            'id' => $opportunity->id,
            'is_published' => true,
            'patient_gender' => CareerOpportunity::GENDER_FEMALE,
            'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
            'patient_age' => 72,
            'application_email' => null,
            'requirements' => null,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $alumni->id,
            'type' => CareerOpportunityPublished::class,
        ]);

        $this->actingAs($alumni)
            ->get(route('alumni.dashboard'))
            ->assertOk()
            ->assertSee('Estimated start')
            ->assertSee('Ambulatory')
            ->assertDontSee('private@example.test')
            ->assertDontSee('Must never be stored');
    }

    public function test_alumni_cannot_see_a_career_draft_or_read_another_account_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $alumni = User::factory()->create(['role' => 'alumni']);
        $otherAlumni = User::factory()->create(['role' => 'alumni']);

        $this->actingAs($admin)->post(route('admin.learning.alumni-jobs.store'), [
            'estimated_start_date' => now()->addDays(5)->toDateString(),
            'patient_gender' => CareerOpportunity::GENDER_FEMALE,
            'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
            'patient_age' => 70,
            'is_published' => '1',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.learning.alumni-jobs.store'), [
            'estimated_start_date' => now()->addDays(6)->toDateString(),
            'patient_gender' => CareerOpportunity::GENDER_MALE,
            'mobility_status' => CareerOpportunity::MOBILITY_BEDRIDDEN,
            'patient_age' => 80,
        ])->assertRedirect();

        $notification = $alumni->notifications()->firstOrFail();

        $this->actingAs($alumni)
            ->get(route('alumni.dashboard'))
            ->assertOk()
            ->assertSee(now()->addDays(5)->format('M d, Y'))
            ->assertDontSee(now()->addDays(6)->format('M d, Y'));

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
            'estimated_start_date' => now()->addDays(10)->toDateString(),
            'patient_gender' => CareerOpportunity::GENDER_MALE,
            'mobility_status' => CareerOpportunity::MOBILITY_BEDRIDDEN,
            'patient_age' => 81,
            'title' => 'Caregiving Duty - Male, Bedridden',
            'employer' => 'MCARE-Coordinated Placement',
            'description' => 'Privacy-minimal duty posting managed through the MCARE Alumni Hub.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.alumni-jobs.preview'))
            ->assertOk()
            ->assertSee('Admin preview of the alumni experience')
            ->assertSee(now()->addDays(10)->format('M d, Y'));

        $this->actingAs($trainee)
            ->get(route('admin.learning.alumni-jobs.preview'))
            ->assertForbidden();
    }

    public function test_alumni_can_set_availability_while_trainees_are_kept_out(): void
    {
        $alumni = User::factory()->create(['role' => 'alumni']);
        $trainee = User::factory()->create(['role' => 'trainee']);

        $this->actingAs($alumni)
            ->patch(route('alumni.availability.update'), ['is_available_for_duty' => '1'])
            ->assertRedirect()
            ->assertSessionHas('saved', 'You are now marked Available for Duty.')
            ->assertSessionHas('saved_icon', 'circle-check');

        $this->assertDatabaseHas('alumni_profiles', [
            'user_id' => $alumni->id,
            'is_available_for_duty' => true,
        ]);

        $availablePage = $this->actingAs($alumni)->get(route('alumni.dashboard'));
        $availablePage
            ->assertOk()
            ->assertSee('data-availability-state="available"', false)
            ->assertSee('data-auto-dismiss="5000"', false)
            ->assertSee('data-flash-icon="circle-check"', false);
        $this->assertSame(1, substr_count(
            $availablePage->getContent(),
            'You are now marked Available for Duty.'
        ));

        $this->actingAs($alumni)
            ->patch(route('alumni.availability.update'), ['is_available_for_duty' => '0'])
            ->assertRedirect()
            ->assertSessionHas('saved', 'Your availability is now set to unavailable.')
            ->assertSessionHas('saved_icon', 'circle-minus');

        $unavailablePage = $this->actingAs($alumni)->get(route('alumni.dashboard'));
        $unavailablePage
            ->assertOk()
            ->assertSee('data-availability-state="unavailable"', false)
            ->assertSee('data-flash-icon="circle-minus"', false);
        $this->assertSame(1, substr_count(
            $unavailablePage->getContent(),
            'Your availability is now set to unavailable.'
        ));

        $this->actingAs($trainee)
            ->get(route('alumni.dashboard'))
            ->assertForbidden();

        $this->actingAs($trainee)
            ->patch(route('alumni.availability.update'), ['is_available_for_duty' => '1'])
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
