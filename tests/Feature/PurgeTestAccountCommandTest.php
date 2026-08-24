<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Notifications\EnrollmentStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeTestAccountCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_test_account_purge_removes_related_rows_files_and_notification_jobs(): void
    {
        config()->set('queue.default', 'database');
        Storage::fake('local');
        $user = User::factory()->create([
            'email' => 'purge.me@gmail.com',
            'role' => 'applicant',
            'applicant_status' => EnrollmentApplication::STATUS_DENIED,
        ]);
        $application = EnrollmentApplication::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Purge',
            'last_name' => 'Applicant',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_DENIED,
            'birth_certificate_path' => 'enrollment-documents/purge/birth.pdf',
            'signature_path' => 'enrollment-documents/purge/signature.png',
        ]);
        PaymentTransaction::create([
            'enrollment_application_id' => $application->id,
            'user_id' => $user->id,
            'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
            'payment_channel' => PaymentTransaction::CHANNEL_ONLINE,
            'amount' => 2000,
            'receipt_proof_path' => 'payment-receipts/purge/receipt.pdf',
            'status' => PaymentTransaction::STATUS_VERIFIED,
        ]);
        foreach ([
            'enrollment-documents/purge/birth.pdf',
            'enrollment-documents/purge/signature.png',
            'payment-receipts/purge/receipt.pdf',
        ] as $path) {
            Storage::disk('local')->put($path, 'test');
        }

        $user->notify(new EnrollmentStatusUpdatedNotification($application));
        $this->assertGreaterThan(0, DB::table('jobs')->count());

        $this->artisan('mcare:purge-test-account', [
            'email' => $user->email,
            '--yes' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('enrollment_applications', ['id' => $application->id]);
        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertDatabaseCount('jobs', 0);
        Storage::disk('local')->assertMissing('enrollment-documents/purge/birth.pdf');
        Storage::disk('local')->assertMissing('enrollment-documents/purge/signature.png');
        Storage::disk('local')->assertMissing('payment-receipts/purge/receipt.pdf');
    }
}
