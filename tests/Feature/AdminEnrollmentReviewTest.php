<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\User;
use App\Notifications\EnrollmentStatusUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminEnrollmentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin/enrollments')
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_open_admin_queue(): void
    {
        $user = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($user)
            ->get('/admin/enrollments')
            ->assertForbidden();
    }

    public function test_admin_can_update_enrollment_review_status(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create([
            'role' => 'applicant',
            'applicant_status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
        ]);

        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => 'applicant@gmail.com',
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
                'admin_notes' => 'Ready for document verification.',
            ])
            ->assertRedirect(route('admin.enrollments.show', $application));

        $this->assertDatabaseHas('enrollment_applications', [
            'id' => $application->id,
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'admin_notes' => 'Ready for document verification.',
            'reviewed_by_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $applicant->id,
            'applicant_status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
        ]);

        Notification::assertSentTo(
            $applicant,
            EnrollmentStatusUpdatedNotification::class,
            fn (EnrollmentStatusUpdatedNotification $notification, array $channels): bool => $notification instanceof ShouldQueue
                && $notification->queue === 'mail'
                && $notification->application->is($application)
                && in_array('database', $channels, true)
                && in_array('mail', $channels, true),
        );
    }

    public function test_admin_can_preview_and_download_filled_tesda_registration_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => 'maria.santos@example.test',
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'employment_status' => 'Unemployed',
            'contact_number' => '09170000000',
            'nationality' => 'Filipino',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'region' => 'NCR',
            'zip_code' => '1100',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE College',
            'year_graduated' => 2022,
            'guardian_name' => 'Juan Santos',
            'guardian_address' => 'Quezon City',
            'privacy_consent' => true,
            'signature_name' => 'Maria Reyes Santos',
            'date_accomplished' => '2026-07-12',
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
        ]);

        $preview = $this->actingAs($admin)->get(route('admin.enrollments.tesda-form', [
            $application,
            'disposition' => 'inline',
        ]));

        $preview->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $preview->getContent());
        $this->assertStringContainsString('inline;', (string) $preview->headers->get('Content-Disposition'));

        $download = $this->actingAs($admin)->get(route('admin.enrollments.tesda-form', [
            $application,
            'disposition' => 'attachment',
        ]));

        $download->assertOk();
        $this->assertStringContainsString('attachment;', (string) $download->headers->get('Content-Disposition'));
    }

    public function test_non_admin_cannot_generate_tesda_registration_form(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => 'private@example.test',
            'program' => 'Caregiving NC II',
            'first_name' => 'Private',
            'last_name' => 'Applicant',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Street',
            'barangay' => 'Barangay',
            'city' => 'City',
            'province' => 'Province',
            'zip_code' => '1000',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'Private College',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
        ]);

        $this->actingAs($applicant)
            ->get(route('admin.enrollments.tesda-form', $application))
            ->assertForbidden();
    }

    public function test_admin_can_preview_documents_and_save_file_specific_feedback(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        Storage::disk('local')->put('enrollment-documents/1/birth.pdf', '%PDF-1.4 sample');
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Document',
            'last_name' => 'Applicant',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Street',
            'barangay' => 'Barangay',
            'city' => 'City',
            'province' => 'Province',
            'zip_code' => '1000',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE College',
            'year_graduated' => 2022,
            'birth_certificate_path' => 'enrollment-documents/1/birth.pdf',
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.documents.show', [$application, 'birth-certificate']))
            ->assertOk()
            ->assertSee('Applicant document preview');

        $this->actingAs($admin)
            ->get(route('admin.enrollments.documents.content', [$application, 'birth-certificate']))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Content-Disposition', 'inline; filename=birth.pdf');

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.documents.review', $application), [
                'documents' => [
                    'birth-certificate' => ['status' => 'replace', 'note' => 'Upload a clearer complete copy.'],
                    'education-document' => ['status' => 'missing', 'note' => 'Diploma is required.'],
                    'good-moral-certificate' => ['status' => 'unreviewed', 'note' => null],
                    'id-photo' => ['status' => 'unreviewed', 'note' => null],
                    'signature' => ['status' => 'unreviewed', 'note' => null],
                ],
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('replace', $application->document_review['birth-certificate']['status']);
        $this->assertSame('Upload a clearer complete copy.', $application->document_review['birth-certificate']['note']);
        $this->assertSame($admin->id, $application->documents_reviewed_by_id);

        $this->actingAs($applicant)
            ->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('Upload a clearer complete copy.');
    }
}
