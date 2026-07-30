<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayMongoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsk_test_webhook_feature';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paymongo.secret_key', 'sk_test_webhook_feature');
        config()->set('services.paymongo.webhook_secret', self::WEBHOOK_SECRET);
        config()->set('services.paymongo.live_mode', false);
        config()->set('services.paymongo.webhook_tolerance', 300);
    }

    public function test_valid_signed_paid_webhook_marks_the_matching_attempt_paid_once(): void
    {
        [$application, $attempt] = $this->pendingOnlinePayment();
        $payload = $this->paidWebhookPayload($application, $attempt, 'evt_test_paid_once');

        $this->postSignedWebhook($payload)->assertOk();
        $this->postSignedWebhook($payload)->assertOk();

        $application->refresh();
        $attempt->refresh();

        $this->assertSame(EnrollmentApplication::PAYMENT_PAID, $application->payment_status);
        $this->assertNotNull($application->payment_verified_at);
        $this->assertNull($application->payment_verified_by_id);
        $this->assertTrue((bool) data_get($application->payment_meta, 'gateway_verified'));
        $this->assertSame('evt_test_paid_once', data_get($application->payment_meta, 'paymongo_event_id'));

        $this->assertSame('paid', $attempt->status);
        $this->assertSame('pay_test_paid_once', $attempt->provider_payment_id);
        $this->assertNotNull($attempt->paid_at);

        $this->assertDatabaseCount('paymongo_webhook_events', 1);
        $this->assertDatabaseHas('paymongo_webhook_events', [
            'event_id' => 'evt_test_paid_once',
            'event_type' => 'checkout_session.payment.paid',
            'resource_id' => 'cs_test_pending_001',
        ]);
    }

    public function test_current_hosted_checkout_envelope_without_event_id_is_supported(): void
    {
        [$application, $attempt] = $this->pendingOnlinePayment();
        $legacyPayload = $this->paidWebhookPayload($application, $attempt, 'evt_not_used');
        $payload = [
            'event_type' => 'send.webhook',
            'data' => [
                'type' => 'checkout_session.payment.paid',
                'resource' => 'checkout_session',
                'livemode' => false,
                'data' => data_get($legacyPayload, 'data.attributes.data'),
            ],
        ];

        $this->postSignedWebhook($payload)->assertOk();

        $this->assertSame(
            EnrollmentApplication::PAYMENT_PAID,
            $application->refresh()->payment_status,
        );
        $this->assertSame(PaymentAttempt::STATUS_PAID, $attempt->refresh()->status);
        $this->assertDatabaseCount('paymongo_webhook_events', 1);
        $this->assertStringStartsWith(
            'payload_',
            (string) DB::table('paymongo_webhook_events')->value('event_id'),
        );
    }

    public function test_webhook_fails_closed_when_signing_secret_is_missing(): void
    {
        [$application, $attempt] = $this->pendingOnlinePayment();
        $payload = $this->paidWebhookPayload($application, $attempt, 'evt_test_missing_secret');
        config()->set('services.paymongo.webhook_secret', null);

        $this->postSignedWebhook($payload)->assertStatus(503);

        $this->assertSame(
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            $application->refresh()->payment_status,
        );
        $this->assertSame(PaymentAttempt::STATUS_PENDING, $attempt->refresh()->status);
        $this->assertDatabaseCount('paymongo_webhook_events', 0);
    }

    public function test_invalid_or_stale_signature_is_rejected_before_state_or_ledger_changes(): void
    {
        [$application, $attempt] = $this->pendingOnlinePayment();
        $payload = $this->paidWebhookPayload($application, $attempt, 'evt_test_bad_signature');
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;

        $this->call(
            'POST',
            route('paymongo.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te=definitely-not-valid,li=",
            ],
            $rawPayload,
        )->assertUnauthorized();

        $staleTimestamp = $timestamp - 301;
        $staleSignature = $this->signatureFor($rawPayload, $staleTimestamp, 'te');

        $this->call(
            'POST',
            route('paymongo.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => $staleSignature,
            ],
            $rawPayload,
        )->assertUnauthorized();

        $this->assertSame(
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            $application->refresh()->payment_status,
        );
        $this->assertSame('pending', $attempt->refresh()->status);
        $this->assertDatabaseCount('paymongo_webhook_events', 0);
    }

    public function test_signed_webhook_cannot_pay_a_mismatched_session_reference_amount_currency_or_mode(): void
    {
        [$application, $attempt] = $this->pendingOnlinePayment();

        $cases = [
            'session' => static function (array &$payload): void {
                data_set($payload, 'data.attributes.data.id', 'cs_test_someone_else');
            },
            'reference' => static function (array &$payload): void {
                data_set(
                    $payload,
                    'data.attributes.data.attributes.reference_number',
                    'MCARE-ONLINE-SOMEONE-ELSE',
                );
                data_set(
                    $payload,
                    'data.attributes.data.attributes.metadata.merchant_reference',
                    'MCARE-ONLINE-SOMEONE-ELSE',
                );
            },
            'amount' => static function (array &$payload): void {
                data_set($payload, 'data.attributes.data.attributes.payments.0.attributes.amount', 199900);
            },
            'currency' => static function (array &$payload): void {
                data_set($payload, 'data.attributes.data.attributes.payments.0.attributes.currency', 'USD');
            },
            'payment_status' => static function (array &$payload): void {
                data_set($payload, 'data.attributes.data.attributes.payments.0.attributes.status', 'failed');
            },
            'mode' => static function (array &$payload): void {
                data_set($payload, 'data.attributes.livemode', true);
                data_set($payload, 'data.attributes.data.attributes.livemode', true);
            },
        ];

        foreach ($cases as $name => $mutate) {
            $payload = $this->paidWebhookPayload(
                $application,
                $attempt,
                'evt_test_mismatch_'.$name,
            );
            $mutate($payload);

            // Keep the signature valid for the configured test endpoint. The
            // resource's livemode flag is an independent reconciliation check.
            $this->postSignedWebhook($payload)->assertSuccessful();

            $this->assertSame(
                EnrollmentApplication::PAYMENT_ONLINE_PENDING,
                $application->refresh()->payment_status,
                "A signed webhook with a mismatched {$name} must not mark the application paid.",
            );
            $this->assertSame(
                'pending',
                $attempt->refresh()->status,
                "A signed webhook with a mismatched {$name} must not mark the attempt paid.",
            );
        }

        $this->assertDatabaseCount('paymongo_webhook_events', count($cases));
    }

    public function test_browser_or_request_fields_cannot_target_another_applications_payment(): void
    {
        [$application, $attempt] = $this->pendingOnlinePayment();
        [$otherApplication] = $this->pendingOnlinePayment(
            userEmail: 'other-applicant@example.test',
            reference: 'MCARE-ONLINE-OTHER-002',
            checkoutId: 'cs_test_other_002',
        );

        $payload = $this->paidWebhookPayload($application, $attempt, 'evt_test_target_binding');
        data_set(
            $payload,
            'data.attributes.data.attributes.metadata.application_id',
            (string) $otherApplication->id,
        );

        $this->postSignedWebhook($payload)->assertSuccessful();

        $this->assertSame(
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            $application->refresh()->payment_status,
        );
        $this->assertSame(
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
            $otherApplication->refresh()->payment_status,
        );
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
     * @return array{0: EnrollmentApplication, 1: PaymentAttempt}
     */
    private function pendingOnlinePayment(
        string $userEmail = 'webhook-applicant@example.test',
        string $reference = 'MCARE-ONLINE-WEBHOOK-001',
        string $checkoutId = 'cs_test_pending_001',
    ): array {
        $user = User::factory()->create([
            'role' => 'applicant',
            'name' => 'Webhook Applicant',
            'email' => $userEmail,
        ]);

        $application = EnrollmentApplication::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Webhook',
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
            'signature_name' => 'Webhook Applicant',
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

        return [$application, $attempt];
    }

    /**
     * @return array<string, mixed>
     */
    private function paidWebhookPayload(
        EnrollmentApplication $application,
        PaymentAttempt $attempt,
        string $eventId,
    ): array {
        return [
            'data' => [
                'id' => $eventId,
                'type' => 'event',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => $attempt->provider_checkout_id,
                        'type' => 'checkout_session',
                        'attributes' => [
                            'livemode' => false,
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
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSignedWebhook(array $payload, string $signatureSlot = 'te')
    {
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;

        return $this->call(
            'POST',
            route('paymongo.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => $this->signatureFor(
                    $rawPayload,
                    $timestamp,
                    $signatureSlot,
                ),
            ],
            $rawPayload,
        );
    }

    private function signatureFor(string $rawPayload, int $timestamp, string $slot): string
    {
        $digest = hash_hmac(
            'sha256',
            $timestamp.'.'.$rawPayload,
            self::WEBHOOK_SECRET,
        );

        return $slot === 'li'
            ? "t={$timestamp},te=,li={$digest}"
            : "t={$timestamp},te={$digest},li=";
    }
}
