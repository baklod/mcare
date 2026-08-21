<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPaymentTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_onsite_downpayment_and_credit_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $application = $this->createApprovedApplication($trainee, $batch);

        $this->actingAs($admin)
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 2000.00,
                'or_number' => 'OR-2026-001',
                'transaction_type' => 'downpayment',
                'paid_at' => now()->toDateString(),
                'notes' => 'Received downpayment at registration desk.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('payment_transactions', [
            'enrollment_application_id' => $application->id,
            'user_id' => $trainee->id,
            'amount' => 2000.00,
            'or_number' => 'OR-2026-001',
            'transaction_type' => 'downpayment',
            'status' => 'verified',
        ]);

        $application->refresh();
        $this->assertEquals(2000.00, (float) $application->total_paid_amount);
        $this->assertEquals(20000.00, $application->remainingBalance());
        $this->assertEquals('partially_paid', $application->payment_status);
        $this->assertTrue($application->isDownpaymentSatisfied());
    }

    public function test_admin_recording_full_tuition_sets_status_to_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $application = $this->createApprovedApplication($trainee, $batch);

        // Record initial downpayment
        $this->actingAs($admin)
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 2000.00,
                'or_number' => 'OR-2026-001',
                'transaction_type' => 'downpayment',
                'paid_at' => now()->toDateString(),
            ]);

        // Record remaining balance
        $this->actingAs($admin)
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 20000.00,
                'or_number' => 'OR-2026-002',
                'transaction_type' => 'balance_settlement',
                'paid_at' => now()->toDateString(),
                'notes' => 'Full balance completed.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $application->refresh();
        $this->assertEquals(22000.00, (float) $application->total_paid_amount);
        $this->assertEquals(0.00, $application->remainingBalance());
        $this->assertEquals('paid', $application->payment_status);
    }

    public function test_trainee_can_upload_payment_proof_and_admin_can_verify(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $application = $this->createApprovedApplication($trainee, $batch);

        $file = UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf');

        $this->actingAs($trainee)
            ->post(route('trainee.payments.upload-proof'), [
                'amount' => 5000.00,
                'or_number' => 'OR-ONLINE-999',
                'transaction_type' => 'installment',
                'paid_at' => now()->toDateString(),
                'receipt_proof' => $file,
                'notes' => 'Paid via cashier bank deposit.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $transaction = PaymentTransaction::query()
            ->where('enrollment_application_id', $application->id)
            ->firstOrFail();

        $this->assertEquals('pending_verification', $transaction->status);
        $this->assertEquals('OR-ONLINE-999', $transaction->or_number);
        $this->assertNotNull($transaction->receipt_proof_path);

        // Admin verifies the transaction
        $this->actingAs($admin)
            ->patch(route('admin.payment-schedules.transactions.verify', $transaction), [
                'action' => 'verify',
                'notes' => 'Validated bank slip against cashier bank feed.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $transaction->refresh();
        $this->assertEquals('verified', $transaction->status);

        $application->refresh();
        $this->assertEquals(5000.00, (float) $application->total_paid_amount);
        $this->assertEquals(17000.00, $application->remainingBalance());
    }

    private function batch(): TrainingBatch
    {
        return TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
    }

    private function createApprovedApplication(User $user, TrainingBatch $batch): EnrollmentApplication
    {
        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birth_date' => '1998-05-12',
            'gender' => 'Male',
            'contact_number' => '09180000000',
            'schedule_preference' => 'AM',
            'street' => 'San Jose St.',
            'barangay' => 'Poblacion',
            'city' => 'Naga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4400',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'University of Nueva Caceres',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 0.00,
            'payment_status' => EnrollmentApplication::PAYMENT_ONSITE_PENDING,
            'payment_method' => 'onsite',
        ]);
    }
}
