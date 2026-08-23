<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\EnrollmentSubmittedNotification;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_can_submit_documents_and_drawn_signature(): void
    {
        Notification::fake();
        Storage::fake('local');

        TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);

        $signature = 'data:image/png;base64,'.base64_encode('fake-signature-bytes');

        $response = $this->post(route('enrollment.store'), [
            'email' => 'applicant@gmail.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'extension_name' => null,
            'birth_date' => '2000-01-01',
            'birthplace_city' => 'Quezon City',
            'birthplace_province' => 'Metro Manila',
            'birthplace_region' => 'NCR',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'employment_status' => 'Unemployed',
            'employment_type' => null,
            'contact_number' => '09170000000',
            'nationality' => 'Filipino',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'region' => 'NCR',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'guardian_name' => 'Ana Santos',
            'guardian_address' => '123 Training Street',
            'classification' => null,
            'disability_type' => null,
            'disability_cause' => null,
            'scholarship_type' => null,
            'privacy_consent' => '1',
            'signature_name' => 'Maria Santos',
            'signature_type' => 'draw',
            'signature_data' => $signature,
            'birth_certificate' => UploadedFile::fake()->create('birth-certificate.pdf', 100, 'application/pdf'),
            'education_document' => UploadedFile::fake()->create('diploma.pdf', 100, 'application/pdf'),
            'good_moral_certificate' => UploadedFile::fake()->create('good-moral.pdf', 100, 'application/pdf'),
            'id_photo' => UploadedFile::fake()->create('id-photo.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('payment.show'));
        // Completing the public enrollment handoff must not silently create an
        // authenticated account session; payment continuation is session-bound.
        $this->assertGuest();
        $this->get(route('payment.show'))->assertOk();

        $this->assertDatabaseHas('enrollment_applications', [
            'email' => 'applicant@gmail.com',
            'signature_type' => 'draw',
            'status' => 'profile_submitted',
        ]);

        $application = EnrollmentApplication::firstOrFail();

        Storage::disk('local')->assertExists($application->birth_certificate_path);
        Storage::disk('local')->assertExists($application->education_document_path);
        Storage::disk('local')->assertExists($application->good_moral_certificate_path);
        Storage::disk('local')->assertExists($application->id_photo_path);
        Storage::disk('local')->assertExists($application->signature_path);

        Notification::assertSentTo(
            $application->user,
            QueuedVerifyEmail::class,
            fn (QueuedVerifyEmail $notification): bool => $notification instanceof ShouldQueue
                && $notification->queue === 'mail',
        );
        Notification::assertSentTo(
            $application->user,
            EnrollmentSubmittedNotification::class,
            fn (EnrollmentSubmittedNotification $notification): bool => $notification instanceof ShouldQueue
                && $notification->queue === 'mail',
        );
    }

    public function test_google_applicant_submits_without_password_and_receives_confirmation(): void
    {
        Notification::fake();
        Storage::fake('local');

        TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);

        $user = User::factory()->create([
            'name' => 'Maria Santos',
            'email' => 'verified.applicant@gmail.com',
            'google_id' => 'google-verified-123',
            'role' => 'applicant',
        ]);
        $originalPassword = $user->password;
        $payload = $this->validEnrollmentPayload([
            // The authenticated account email wins over a tampered form value.
            'email' => 'different.account@gmail.com',
        ]);
        unset($payload['password'], $payload['password_confirmation']);

        $this->actingAs($user)
            ->post(route('enrollment.store'), $payload)
            ->assertRedirect(route('payment.show'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($originalPassword, $user->refresh()->password);
        $this->assertDatabaseHas('enrollment_applications', [
            'user_id' => $user->id,
            'email' => 'verified.applicant@gmail.com',
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
        ]);

        Notification::assertSentTo(
            $user,
            EnrollmentSubmittedNotification::class,
            fn (EnrollmentSubmittedNotification $notification, array $channels): bool => $notification instanceof ShouldQueue
                && $notification->queue === 'mail'
                && in_array('database', $channels, true)
                && in_array('mail', $channels, true),
        );
    }

    public function test_applicant_can_generate_pay_on_site_receipt(): void
    {
        $user = User::factory()->create();
        EnrollmentApplication::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
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
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'guardian_name' => 'Ana Santos',
            'guardian_address' => '123 Training Street',
            'privacy_consent' => true,
            'signature_name' => 'Maria Santos',
            'date_accomplished' => now()->toDateString(),
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
        ]);

        $this->actingAs($user)
            ->post(route('payment.select'), [
                'payment_method' => 'onsite',
            ])
            ->assertRedirect(route('payment.receipt'));

        $application = EnrollmentApplication::firstOrFail();

        $this->assertSame(EnrollmentApplication::PAYMENT_ONSITE_PENDING, $application->payment_status);
        $this->assertSame('onsite', $application->payment_method);
        $this->assertNotNull($application->payment_reference);
        $this->assertNotNull($application->payment_receipt_number);
        $this->assertTrue($application->payment_receipt_expires_at->isFuture());
    }

    /** @return array<string, mixed> */
    private function validEnrollmentPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'applicant@gmail.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'extension_name' => null,
            'birth_date' => '2000-01-01',
            'birthplace_city' => 'Quezon City',
            'birthplace_province' => 'Metro Manila',
            'birthplace_region' => 'NCR',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'employment_status' => 'Unemployed',
            'employment_type' => null,
            'contact_number' => '09170000000',
            'nationality' => 'Filipino',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'region' => 'NCR',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'guardian_name' => 'Ana Santos',
            'guardian_address' => '123 Training Street',
            'classification' => null,
            'disability_type' => null,
            'disability_cause' => null,
            'scholarship_type' => null,
            'privacy_consent' => '1',
            'signature_name' => 'Maria Santos',
            'signature_type' => 'draw',
            'signature_data' => 'data:image/png;base64,'.base64_encode('fake-signature-bytes'),
            'birth_certificate' => UploadedFile::fake()->create('birth-certificate.pdf', 100, 'application/pdf'),
            'education_document' => UploadedFile::fake()->create('diploma.pdf', 100, 'application/pdf'),
            'good_moral_certificate' => UploadedFile::fake()->create('good-moral.pdf', 100, 'application/pdf'),
            'id_photo' => UploadedFile::fake()->create('id-photo.jpg', 100, 'image/jpeg'),
        ], $overrides);
    }
}
