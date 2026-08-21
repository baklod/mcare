<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraineePaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainee_can_generate_onsite_receipt_and_spamming_keeps_single_output(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        $application = EnrollmentApplication::create([
            'user_id' => $trainee->id,
            'training_batch_id' => $batch->id,
            'email' => $trainee->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'birth_date' => '2001-03-15',
            'gender' => 'Female',
            'contact_number' => '09191234567',
            'schedule_preference' => 'AM',
            'street' => 'Magsaysay Ave',
            'barangay' => 'San Roque',
            'city' => 'Naga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4400',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'Ateneo de Naga',
            'year_graduated' => 2023,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 0.00,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
        ]);

        // First click: Generates the on-site payment slip
        $response1 = $this->actingAs($trainee)
            ->post(route('payment.select'), [
                'payment_method' => 'onsite',
            ]);

        $response1->assertRedirect(route('payment.receipt'));

        $application->refresh();
        $firstReceiptNumber = $application->payment_receipt_number;
        $firstReference = $application->payment_reference;

        $this->assertNotEmpty($firstReceiptNumber);
        $this->assertNotEmpty($firstReference);
        $this->assertEquals('onsite', $application->payment_method);
        $this->assertEquals(EnrollmentApplication::PAYMENT_ONSITE_PENDING, $application->payment_status);

        // Spam clicks: 5 repeated calls simulating button mash / double submit
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($trainee)
                ->post(route('payment.select'), [
                    'payment_method' => 'onsite',
                ])
                ->assertRedirect(route('payment.receipt'));
        }

        $application->refresh();

        // Must still equal the EXACT same receipt and reference number (single output)
        $this->assertEquals($firstReceiptNumber, $application->payment_receipt_number);
        $this->assertEquals($firstReference, $application->payment_reference);

        // Trainee can open and view the receipt page
        $this->actingAs($trainee)
            ->get(route('payment.receipt'))
            ->assertOk()
            ->assertSee($firstReceiptNumber)
            ->assertSee('Ana Reyes')
            ->assertSee('PHP 22,000.00');

        // Trainee billing summary shows the active slip details
        $this->actingAs($trainee)
            ->get(route('trainee.payments'))
            ->assertOk()
            ->assertSee($firstReceiptNumber);
    }
}
