<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentPaymentLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_page_asks_for_the_enrollment_number_first(): void
    {
        $this->get(route('payments.show'))
            ->assertOk()
            ->assertSee('Enrollment number', false)
            ->assertSee('Look up payment', false)
            ->assertDontSee('Pay with PayMongo', false);
    }

    public function test_unknown_enrollment_number_does_not_show_payment_methods(): void
    {
        $this->post(route('payments.lookup'), [
            'enrollment_number' => 'MCE-2026-NOTFND',
        ])->assertRedirect(route('payments.show', [
            'enrollment_number' => 'MCE-2026-NOTFND',
        ]));

        $this->get(route('payments.show', ['enrollment_number' => 'MCE-2026-NOTFND']))
            ->assertOk()
            ->assertSee('That enrollment number was not found', false)
            ->assertDontSee('Pay with PayMongo', false);
    }

    public function test_unpaid_enrollment_number_shows_payment_methods(): void
    {
        $application = $this->enrollmentApplication();

        $this->get(route('payments.show', [
            'enrollment_number' => $application->enrollment_number,
        ]))
            ->assertOk()
            ->assertSee($application->enrollment_number, false)
            ->assertSee('Lookup Applicant', false)
            ->assertSee('lookup-applicant@example.test', false)
            ->assertSee('Caregiving NC II', false)
            ->assertSee('PHP 2,000.00', false)
            ->assertSee('Not selected', false)
            ->assertSee('09170000000', false)
            ->assertSee('Quezon City', false)
            ->assertSee('Continue with selected method', false)
            ->assertSee('Confirm PayMongo payment', false)
            ->assertSee('Confirm pay on site', false)
            ->assertSee('PayMongo checkout', false)
            ->assertSee('Cashier receipt', false);
    }

    public function test_onsite_pending_enrollment_disables_continue(): void
    {
        $application = $this->enrollmentApplication([
            'payment_status' => EnrollmentApplication::PAYMENT_ONSITE_PENDING,
            'payment_method' => 'onsite',
            'payment_receipt_number' => 'MCR-260830-TESTRECEIPT',
            'payment_receipt_expires_at' => now()->addDays(3),
        ]);

        $this->get(route('payments.show', [
            'enrollment_number' => $application->enrollment_number,
        ]))
            ->assertOk()
            ->assertSee('Pay on site is already selected', false)
            ->assertSee('View receipt', false)
            ->assertSeeInOrder([
                'data-payment-continue',
                'disabled',
                'Continue with selected method',
            ], false);
    }

    public function test_paid_enrollment_number_hides_payment_methods(): void
    {
        $application = $this->enrollmentApplication([
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'payment_verified_at' => now(),
            'total_paid_amount' => 2000,
            'downpayment_amount' => 2000,
        ]);

        $this->get(route('payments.show', [
            'enrollment_number' => strtolower($application->enrollment_number),
        ]))
            ->assertOk()
            ->assertSee($application->enrollment_number, false)
            ->assertSee('Lookup Applicant', false)
            ->assertSee('lookup-applicant@example.test', false)
            ->assertSee('Caregiving NC II', false)
            ->assertSee('Fully paid', false)
            ->assertSee('This enrollment is already paid', false)
            ->assertDontSee('Continue with selected method', false)
            ->assertDontSee('PayMongo checkout', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function enrollmentApplication(array $overrides = []): EnrollmentApplication
    {
        $user = User::factory()->create([
            'role' => 'applicant',
            'name' => 'Lookup Applicant',
            'email' => 'lookup-applicant@example.test',
        ]);

        return EnrollmentApplication::create(array_merge([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Lookup',
            'last_name' => 'Applicant',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'employment_status' => 'Unemployed',
            'contact_number' => '09170000000',
            'nationality' => 'Filipino',
            'schedule_preference' => 'AM',
            'street' => '123 Test Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'region' => 'NCR',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'Test School',
            'year_graduated' => 2020,
            'privacy_consent' => true,
            'signature_name' => 'Lookup Applicant',
            'date_accomplished' => now()->toDateString(),
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
            'payment_amount' => 2000,
            'payment_currency' => 'PHP',
        ], $overrides));
    }
}
