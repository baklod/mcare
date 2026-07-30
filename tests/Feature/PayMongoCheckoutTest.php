<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayMongoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paymongo.secret_key', 'sk_test_checkout_feature');
        config()->set('services.paymongo.webhook_secret', 'whsk_test_webhook_feature');
        config()->set('services.paymongo.live_mode', false);
        config()->set('services.paymongo.payment_methods', ['card', 'gcash', 'qrph']);
        config()->set('logging.default', 'null');

        Http::preventStrayRequests();
    }

    public function test_online_checkout_is_created_server_side_and_reused_safely(): void
    {
        [$user, $application] = $this->applicantWithApplication();

        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' => Http::response(
                $this->checkoutResponse('cs_test_secure_checkout'),
                200,
            ),
        ]);

        $firstResponse = $this->actingAs($user)->post(route('payment.select'), [
            'payment_method' => 'online',
        ]);

        $firstResponse->assertRedirect('https://checkout.paymongo.com/cs_test_secure_checkout');

        $application->refresh();

        $this->assertSame('online', $application->payment_method);
        $this->assertSame(EnrollmentApplication::PAYMENT_ONLINE_PENDING, $application->payment_status);
        $this->assertSame('2000.00', $application->payment_amount);
        $this->assertSame('PHP', $application->payment_currency);
        $this->assertNotNull($application->payment_reference);
        $this->assertSame('cs_test_secure_checkout', $application->paymongo_checkout_reference);
        $this->assertSame(
            'https://checkout.paymongo.com/cs_test_secure_checkout',
            $application->paymongo_checkout_url,
        );

        $this->assertDatabaseHas('payment_attempts', [
            'enrollment_application_id' => $application->id,
            'provider' => 'paymongo',
            'merchant_reference' => $application->payment_reference,
            'provider_checkout_id' => 'cs_test_secure_checkout',
            'amount_minor' => 200000,
            'currency' => 'PHP',
            'status' => 'pending',
            'livemode' => false,
        ]);

        $this->assertDatabaseCount('payment_attempts', 1);
        $this->assertDatabaseMissing('payment_attempts', [
            'enrollment_application_id' => $application->id,
            'status' => 'paid',
        ]);

        Http::assertSent(function (HttpRequest $request) use ($application): bool {
            $attributes = data_get($request->data(), 'data.attributes');

            return $request->method() === 'POST'
                && $request->url() === 'https://api.paymongo.com/v2/checkout_sessions'
                && $request->hasHeader(
                    'Authorization',
                    'Basic '.base64_encode('sk_test_checkout_feature:'),
                )
                && filled($request->header('Idempotency-Key')[0] ?? null)
                && data_get($attributes, 'line_items.0.amount') === 200000
                && data_get($attributes, 'line_items.0.currency') === 'PHP'
                && data_get($attributes, 'line_items.0.quantity') === 1
                && data_get($attributes, 'reference_number') === $application->payment_reference
                && data_get($attributes, 'metadata.application_id') === (string) $application->id
                && data_get($attributes, 'metadata.merchant_reference') === $application->payment_reference
                && data_get($attributes, 'success_url') === route('payment.return')
                && data_get($attributes, 'cancel_url') === route('payment.cancel')
                && data_get($attributes, 'payment_method_types') === ['card', 'gcash', 'qrph'];
        });

        // A retry or double click must reuse the existing provider session instead
        // of charging through a second Checkout Session.
        $this->actingAs($user)
            ->post(route('payment.select'), ['payment_method' => 'online'])
            ->assertRedirect('https://checkout.paymongo.com/cs_test_secure_checkout');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('payment_attempts', 1);
    }

    public function test_checkout_rejects_an_untrusted_redirect_url(): void
    {
        [$user, $application] = $this->applicantWithApplication();

        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_untrusted',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'checkout_url' => 'https://attacker.example/collect-payment',
                        'livemode' => false,
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('payment.select'), ['payment_method' => 'online'])
            ->assertRedirect(route('payment.show'))
            ->assertSessionHasErrors('payment');

        $application->refresh();

        $this->assertNotSame(EnrollmentApplication::PAYMENT_PAID, $application->payment_status);
        $this->assertNull($application->paymongo_checkout_reference);
        $this->assertNull($application->paymongo_checkout_url);
    }

    public function test_provider_failure_is_safe_and_retryable_without_marking_payment_paid(): void
    {
        [$user, $application] = $this->applicantWithApplication();

        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' => Http::response([
                'errors' => [
                    ['code' => 'service_unavailable', 'detail' => 'Provider diagnostic must stay private.'],
                ],
            ], 503),
        ]);

        $this->actingAs($user)
            ->post(route('payment.select'), ['payment_method' => 'online'])
            ->assertRedirect(route('payment.show'))
            ->assertSessionHasErrors('payment');

        $application->refresh();

        $this->assertNotSame(EnrollmentApplication::PAYMENT_PAID, $application->payment_status);
        $this->assertNull($application->paymongo_checkout_reference);
        $this->assertNull($application->paymongo_checkout_url);
        $this->assertNull($application->payment_verified_at);
    }

    public function test_idempotency_conflict_reuses_the_same_key_and_immutable_payload(): void
    {
        [$user, $application] = $this->applicantWithApplication();

        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' => Http::sequence()
                ->push(['errors' => [['code' => 'idempotency_key_in_use']]], 409)
                ->push($this->checkoutResponse('cs_test_after_conflict'), 200),
        ]);

        $this->actingAs($user)
            ->post(route('payment.select'), ['payment_method' => 'online'])
            ->assertRedirect(route('payment.show'))
            ->assertSessionHasErrors('payment');

        $attempt = PaymentAttempt::firstOrFail();
        $this->assertSame(PaymentAttempt::STATUS_CREATING, $attempt->status);
        $this->assertSame(
            EnrollmentApplication::PAYMENT_NOT_SELECTED,
            $application->refresh()->payment_status,
        );

        // Runtime configuration can change, but a retry with the same key must
        // send the exact request persisted for the original logical checkout.
        config()->set('services.paymongo.payment_methods', ['gcash']);

        $this->actingAs($user)
            ->post(route('payment.select'), ['payment_method' => 'online'])
            ->assertRedirect('https://checkout.paymongo.com/cs_test_after_conflict');

        $recorded = Http::recorded();
        $this->assertCount(2, $recorded);
        $this->assertSame(
            $recorded[0][0]->header('Idempotency-Key')[0],
            $recorded[1][0]->header('Idempotency-Key')[0],
        );
        $this->assertSame($recorded[0][0]->data(), $recorded[1][0]->data());
        $this->assertDatabaseCount('payment_attempts', 1);
        $this->assertSame(PaymentAttempt::STATUS_PENDING, $attempt->refresh()->status);
    }

    public function test_online_checkout_fails_closed_until_the_webhook_secret_is_configured(): void
    {
        [$user, $application] = $this->applicantWithApplication();
        config()->set('services.paymongo.webhook_secret', null);
        Http::fake();

        $this->actingAs($user)
            ->post(route('payment.select'), ['payment_method' => 'online'])
            ->assertRedirect(route('payment.show'))
            ->assertSessionHasErrors('payment');

        Http::assertNothingSent();
        $this->assertSame(
            EnrollmentApplication::PAYMENT_NOT_SELECTED,
            $application->refresh()->payment_status,
        );
        $this->assertNull($application->paymongo_checkout_reference);
        $this->assertNull($application->payment_verified_at);
    }

    public function test_paid_application_cannot_be_regressed_by_starting_another_checkout(): void
    {
        [$user, $application] = $this->applicantWithApplication([
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'payment_reference' => 'MCARE-ONLINE-ALREADY-PAID',
            'paymongo_checkout_reference' => 'cs_test_already_paid',
            'payment_selected_at' => now()->subMinute(),
            'payment_verified_at' => now(),
            'payment_meta' => ['gateway_verified' => true],
        ]);
        Http::fake();

        $response = $this->actingAs($user)->post(route('payment.select'), [
            'payment_method' => 'online',
        ]);

        $this->assertTrue($response->isRedirect());

        $this->actingAs($user)->post(route('payment.select'), [
            'payment_method' => 'onsite',
        ])->assertRedirect(route('payment.show'));

        Http::assertNothingSent();
        $this->assertSame(
            EnrollmentApplication::PAYMENT_PAID,
            $application->refresh()->payment_status,
        );
        $this->assertNotNull($application->payment_verified_at);
    }

    public function test_active_gateway_attempt_cannot_be_replaced_by_onsite_payment(): void
    {
        [$user, $application] = $this->applicantWithApplication([
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_EXPIRED,
            'payment_reference' => 'MCARE-ONLINE-STILL-PAYABLE',
            'paymongo_checkout_reference' => 'cs_test_still_payable',
            'paymongo_checkout_url' => 'https://checkout.paymongo.com/cs_test_still_payable',
            'payment_selected_at' => now()->subHour(),
        ]);
        PaymentAttempt::create([
            'enrollment_application_id' => $application->id,
            'provider' => 'paymongo',
            'merchant_reference' => $application->payment_reference,
            'idempotency_key' => Str::uuid()->toString(),
            'provider_checkout_id' => $application->paymongo_checkout_reference,
            'amount_minor' => 200000,
            'currency' => 'PHP',
            'status' => PaymentAttempt::STATUS_PENDING,
            'checkout_url' => $application->paymongo_checkout_url,
            'livemode' => false,
        ]);

        $this->actingAs($user)
            ->post(route('payment.select'), ['payment_method' => 'onsite'])
            ->assertRedirect(route('payment.show'))
            ->assertSessionHasErrors('payment');

        $application->refresh();
        $this->assertSame('online', $application->payment_method);
        $this->assertSame('MCARE-ONLINE-STILL-PAYABLE', $application->payment_reference);
        $this->assertSame('cs_test_still_payable', $application->paymongo_checkout_reference);
        $this->assertNull($application->payment_receipt_number);
    }

    public function test_active_attempt_cannot_cross_from_test_mode_to_live_mode(): void
    {
        [$user, $application] = $this->applicantWithApplication([
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            'payment_reference' => 'MCARE-ONLINE-TEST-MODE',
            'payment_selected_at' => now(),
        ]);
        $attempt = PaymentAttempt::create([
            'enrollment_application_id' => $application->id,
            'provider' => 'paymongo',
            'merchant_reference' => $application->payment_reference,
            'idempotency_key' => Str::uuid()->toString(),
            'amount_minor' => 200000,
            'currency' => 'PHP',
            'status' => PaymentAttempt::STATUS_CREATING,
            'livemode' => false,
            'meta' => ['checkout_payload' => ['data' => ['attributes' => []]]],
        ]);

        config()->set('services.paymongo.secret_key', 'sk_live_checkout_feature');
        config()->set('services.paymongo.live_mode', true);
        Http::fake();

        $this->actingAs($user)
            ->post(route('payment.select'), ['payment_method' => 'online'])
            ->assertRedirect(route('payment.show'))
            ->assertSessionHasErrors('payment');

        Http::assertNothingSent();
        $this->assertFalse($attempt->refresh()->livemode);
        $this->assertSame(PaymentAttempt::STATUS_CREATING, $attempt->status);
        $this->assertDatabaseCount('payment_attempts', 1);
    }

    public function test_browser_return_cannot_mark_an_online_payment_as_paid(): void
    {
        [$user, $application] = $this->applicantWithApplication([
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            'payment_reference' => 'MCARE-ONLINE-RETURN-001',
            'paymongo_checkout_reference' => 'cs_test_return_only',
            'paymongo_checkout_url' => 'https://checkout.paymongo.com/cs_test_return_only',
            'payment_selected_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('payment.return'));

        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirect(),
            'The safe return endpoint should render or redirect without a server error.',
        );
        $this->assertSame(
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            $application->refresh()->payment_status,
        );
        $this->assertNull($application->payment_verified_at);
        $this->assertNull($application->payment_verified_by_id);
    }

    public function test_payment_status_poll_reads_server_state_without_mutating_it(): void
    {
        [$user, $application] = $this->applicantWithApplication([
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            'payment_reference' => 'MCARE-ONLINE-POLL-001',
            'paymongo_checkout_reference' => 'cs_test_poll_only',
            'paymongo_checkout_url' => 'https://checkout.paymongo.com/cs_test_poll_only',
            'payment_selected_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson(route('payment.status'))
            ->assertOk()
            ->assertJson([
                'status' => EnrollmentApplication::PAYMENT_ONLINE_PENDING,
                'paid' => false,
            ]);

        $this->assertSame(
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            $application->refresh()->payment_status,
        );
        $this->assertNull($application->payment_verified_at);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: User, 1: EnrollmentApplication}
     */
    private function applicantWithApplication(array $overrides = []): array
    {
        $user = User::factory()->create([
            'role' => 'applicant',
            'name' => 'Secure Checkout Applicant',
            'email' => 'secure-checkout@example.test',
        ]);

        $application = EnrollmentApplication::create(array_merge([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Secure',
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
            'signature_name' => 'Secure Applicant',
            'date_accomplished' => now()->toDateString(),
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
            'payment_amount' => 2000,
            'payment_currency' => 'PHP',
        ], $overrides));

        return [$user, $application];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutResponse(string $checkoutId): array
    {
        return [
            'data' => [
                'id' => $checkoutId,
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/'.$checkoutId,
                    'livemode' => false,
                    'status' => 'active',
                ],
            ],
        ];
    }
}
