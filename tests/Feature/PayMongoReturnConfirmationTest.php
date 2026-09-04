<?php

namespace Tests\Feature;

use App\Mail\PaymentReceiptMail;
use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayMongoReturnConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paymongo.secret_key', 'sk_test_checkout_feature');
        config()->set('services.paymongo.live_mode', false);
        config()->set('logging.default', 'null');
        Notification::fake();
        Mail::fake();
        Http::preventStrayRequests();
    }

    public function test_return_from_paymongo_marks_a_paid_checkout_once(): void
    {
        [$application, $attempt, $user] = $this->pendingOnlinePayment();

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_pending_001' => Http::response(
                $this->paidCheckoutResponse($application, $attempt),
                200,
            ),
        ]);

        $this->actingAs($user)
            ->get(route('payment.return'))
            ->assertRedirect(route('payment.complete'));

        $this->actingAs($user)
            ->get(route('payment.return'))
            ->assertRedirect(route('payment.complete'));

        $application->refresh();
        $attempt->refresh();

        $this->assertSame(EnrollmentApplication::PAYMENT_PARTIALLY_PAID, $application->payment_status);
        $this->assertSame(2000.00, (float) $application->total_paid_amount);
        $this->assertTrue($application->isDownpaymentSatisfied());
        $this->assertNotNull($application->payment_verified_at);
        $this->assertNull($application->payment_verified_by_id);
        $this->assertTrue((bool) data_get($application->payment_meta, 'gateway_verified'));
        $this->assertSame('paid', $attempt->status);
        $this->assertSame('pay_test_paid_once', $attempt->provider_payment_id);
        $this->assertDatabaseHas('payment_transactions', [
            'enrollment_application_id' => $application->id,
            'payment_channel' => PaymentTransaction::CHANNEL_ONLINE,
            'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
            'amount' => 2000.00,
            'status' => PaymentTransaction::STATUS_VERIFIED,
            'reference_number' => 'pay_test_paid_once',
        ]);
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertStringStartsWith('MCARE-OR-', (string) $application->payment_receipt_number);
        $this->assertSame($application->payment_receipt_number, $application->paymentTransactions()->first()?->or_number);

        Mail::assertSent(PaymentReceiptMail::class, 1);
        Mail::assertSent(PaymentReceiptMail::class, function (PaymentReceiptMail $mail) use ($application, $user): bool {
            $html = $mail->render();

            return $mail->hasTo($user->email)
                && $mail->application->is($application)
                && str_contains($html, 'PayMongo')
                && str_contains($html, 'Official payment receipt')
                && str_contains($html, 'Official Receipt (OR) #')
                && str_contains($html, (string) $application->payment_receipt_number)
                && str_contains($html, 'Reference number')
                && str_contains($html, (string) $application->payment_reference)
                && str_contains($html, 'PayMongo payment number')
                && str_contains($html, 'pay_test_paid_once');
        });

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.paymongo.com/v1/checkout_sessions/cs_test_pending_001';
        });
    }

    public function test_return_marks_paid_when_the_stored_payment_amount_does_not_match_the_attempt(): void
    {
        [$application, $attempt, $user] = $this->pendingOnlinePayment();
        $application->forceFill([
            'downpayment_amount' => 1,
            'payment_amount' => 2000,
        ])->save();
        $attempt->forceFill([
            'amount_minor' => 100,
        ])->save();

        $payload = $this->paidCheckoutResponse($application->refresh(), $attempt->refresh());
        data_set($payload, 'data.attributes.payments.0.attributes.amount', 100);

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_pending_001' => Http::response($payload, 200),
        ]);

        $this->actingAs($user)
            ->get(route('payment.return'))
            ->assertRedirect(route('payment.complete'));

        $application->refresh();
        $attempt->refresh();

        $this->assertSame(1.0, (float) $application->payment_amount);
        $this->assertSame(1.0, (float) $application->total_paid_amount);
        $this->assertTrue($application->isDownpaymentSatisfied());
        $this->assertSame('paid', $attempt->status);
        $this->assertSame('pay_test_paid_once', $attempt->provider_payment_id);
    }

    public function test_return_accepts_numeric_string_paid_amounts_from_paymongo(): void
    {
        [$application, $attempt, $user] = $this->pendingOnlinePayment();
        $payload = $this->paidCheckoutResponse($application, $attempt);
        data_set($payload, 'data.attributes.payments.0.attributes.amount', '200000');

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_pending_001' => Http::response($payload, 200),
        ]);

        $this->actingAs($user)
            ->get(route('payment.return'))
            ->assertRedirect(route('payment.complete'));

        $this->assertSame('paid', $attempt->refresh()->status);
        $this->assertNotNull($application->refresh()->payment_verified_at);
    }

    public function test_payment_page_confirms_a_paid_checkout_without_waiting_for_the_return_url(): void
    {
        [$application, $attempt, $user] = $this->pendingOnlinePayment();

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_pending_001' => Http::response(
                $this->paidCheckoutResponse($application, $attempt),
                200,
            ),
        ]);

        $this->actingAs($user)
            ->get(route('payment.show'))
            ->assertRedirect(route('payment.complete'));

        $this->assertSame('paid', $attempt->refresh()->status);
        $this->assertNotNull($application->refresh()->payment_verified_at);
    }

    public function test_return_stays_pending_when_paymongo_has_not_marked_the_checkout_paid(): void
    {
        [$application, $attempt, $user] = $this->pendingOnlinePayment();

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_pending_001' => Http::response([
                'data' => [
                    'id' => 'cs_test_pending_001',
                    'attributes' => [
                        'livemode' => false,
                        'status' => 'active',
                        'reference_number' => $attempt->merchant_reference,
                        'metadata' => [
                            'application_id' => (string) $application->id,
                            'merchant_reference' => $attempt->merchant_reference,
                        ],
                        'payments' => [],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->get(route('payment.return'))
            ->assertRedirect(route('payment.show'));

        $this->assertSame(
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            $application->refresh()->payment_status,
        );
        $this->assertSame(PaymentAttempt::STATUS_PENDING, $attempt->refresh()->status);
        $this->assertNull($application->payment_verified_at);
    }

    public function test_return_stays_pending_when_paymongo_cannot_be_reached(): void
    {
        [$application, $attempt, $user] = $this->pendingOnlinePayment();

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_pending_001' => Http::response([], 503),
        ]);

        $this->actingAs($user)
            ->get(route('payment.return'))
            ->assertRedirect(route('payment.show'))
            ->assertSessionHasErrors('payment');

        $this->assertSame(
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            $application->refresh()->payment_status,
        );
        $this->assertSame(PaymentAttempt::STATUS_PENDING, $attempt->refresh()->status);
        $this->assertNull($application->payment_verified_at);
    }

    public function test_mismatched_checkout_amount_or_reference_is_not_marked_paid(): void
    {
        [$application, $attempt, $user] = $this->pendingOnlinePayment();
        $payload = $this->paidCheckoutResponse($application, $attempt);
        data_set($payload, 'data.attributes.payments.0.attributes.amount', 999999);

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_pending_001' => Http::response($payload, 200),
        ]);

        $this->actingAs($user)
            ->get(route('payment.return'))
            ->assertRedirect(route('payment.show'));

        $this->assertSame(
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            $application->refresh()->payment_status,
        );
        $this->assertSame(PaymentAttempt::STATUS_PENDING, $attempt->refresh()->status);
    }

    public function test_admin_cannot_manually_verify_an_online_payment(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'paymongo-admin@example.test',
        ]);
        [$application] = $this->pendingOnlinePayment();

        $this->actingAs($admin)
            ->patch(route('admin.payment-schedules.update', $application), [
                'action' => 'verify_paid',
                'payment_verification_notes' => 'Manual override should be refused.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        $application->refresh();

        $this->assertSame(EnrollmentApplication::PAYMENT_ONLINE_PENDING, $application->payment_status);
        $this->assertNull($application->payment_verified_at);
        $this->assertNull($application->payment_verified_by_id);
    }

    /**
     * @return array{0: EnrollmentApplication, 1: PaymentAttempt, 2: User}
     */
    private function pendingOnlinePayment(
        string $userEmail = 'return-applicant@example.test',
        string $reference = 'MCARE-ONLINE-RETURN-001',
        string $checkoutId = 'cs_test_pending_001',
    ): array {
        $user = User::factory()->create([
            'role' => 'applicant',
            'name' => 'Return Applicant',
            'email' => $userEmail,
        ]);

        $application = EnrollmentApplication::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Return',
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
            'signature_name' => 'Return Applicant',
            'date_accomplished' => now()->toDateString(),
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            'payment_amount' => 2000,
            'payment_currency' => 'PHP',
            'payment_reference' => $reference,
            'paymongo_checkout_reference' => $checkoutId,
            'paymongo_checkout_url' => 'https://checkout.paymongo.com/'.$checkoutId,
            'payment_selected_at' => now(),
            'payment_meta' => [
                'channel' => 'paymongo',
                'gateway_verified' => false,
            ],
        ]);

        $attempt = PaymentAttempt::create([
            'enrollment_application_id' => $application->id,
            'provider' => 'paymongo',
            'merchant_reference' => $reference,
            'idempotency_key' => Str::uuid()->toString(),
            'provider_checkout_id' => $checkoutId,
            'amount_minor' => 200000,
            'currency' => 'PHP',
            'status' => 'pending',
            'checkout_url' => 'https://checkout.paymongo.com/'.$checkoutId,
            'livemode' => false,
            'meta' => [
                'application_id' => $application->id,
            ],
        ]);

        return [$application, $attempt, $user];
    }

    /**
     * @return array<string, mixed>
     */
    private function paidCheckoutResponse(EnrollmentApplication $application, PaymentAttempt $attempt): array
    {
        return [
            'data' => [
                'id' => $attempt->provider_checkout_id,
                'type' => 'checkout_session',
                'attributes' => [
                    'livemode' => false,
                    'status' => 'paid',
                    'reference_number' => $attempt->merchant_reference,
                    'metadata' => [
                        'application_id' => (string) $application->id,
                        'merchant_reference' => $attempt->merchant_reference,
                    ],
                    'payments' => [
                        [
                            'id' => 'pay_test_paid_once',
                            'type' => 'payment',
                            'attributes' => [
                                'status' => 'paid',
                                'amount' => 200000,
                                'currency' => 'PHP',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
