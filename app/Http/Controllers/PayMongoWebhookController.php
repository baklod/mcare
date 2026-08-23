<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Models\PaymongoWebhookEvent;
use App\Services\EnrollmentPaymentLifecycle;
use App\Support\PayMongoWebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JsonException;

class PayMongoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PayMongoWebhookSignature $signature,
        EnrollmentPaymentLifecycle $paymentLifecycle,
    ): JsonResponse
    {
        if (! $signature->configured()) {
            return response()->json([
                'message' => 'Webhook verification is unavailable.',
            ], 503);
        }

        $rawBody = $request->getContent();

        if (! $signature->verify($rawBody, $request->header('Paymongo-Signature'))) {
            return response()->json([
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json([
                'message' => 'Invalid webhook payload.',
            ], 400);
        }

        if (! is_array($payload)) {
            return response()->json([
                'message' => 'Invalid webhook payload.',
            ], 400);
        }

        $envelope = $this->eventEnvelope($payload, $rawBody);

        if (! $envelope) {
            return response()->json([
                'message' => 'Invalid webhook event.',
            ], 400);
        }

        $result = DB::transaction(function () use ($envelope, $rawBody): array {
            $now = now();
            $inserted = DB::table('paymongo_webhook_events')->insertOrIgnore([
                'event_id' => $envelope['event_id'],
                'event_type' => $envelope['event_type'],
                'resource_id' => $envelope['resource']['id'] ?? null,
                'livemode' => (bool) ($envelope['livemode'] ?? false),
                'payload_sha256' => hash('sha256', $rawBody),
                'status' => 'received',
                'received_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($inserted === 0) {
                return [
                    'status' => 'duplicate',
                    'http_status' => 200,
                ];
            }

            $event = PaymongoWebhookEvent::query()
                ->where('event_id', $envelope['event_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($envelope['event_type'] !== 'checkout_session.payment.paid') {
                return $this->finishEvent($event, 'ignored', null, 200);
            }

            if (
                ! is_bool($envelope['livemode'])
                || $envelope['livemode'] !== (bool) config('services.paymongo.live_mode', false)
            ) {
                return $this->finishEvent($event, 'rejected', 'mode_mismatch', 202);
            }

            return $this->processPaidCheckout($event, $envelope);
        }, 3);

        if (isset($result['payment_verified_application_id'])) {
            $application = EnrollmentApplication::query()->find($result['payment_verified_application_id']);

            if ($application) {
                $paymentLifecycle->handleVerifiedPayment($application);
            }
        }

        return response()->json([
            'received' => true,
            'status' => $result['status'],
        ], $result['http_status']);
    }

    /**
     * Support both PayMongo's event-resource envelope and its newer hosted
     * checkout example while keeping the original raw bytes for verification.
     *
     * @return array{
     *     event_id: string,
     *     event_type: string,
     *     livemode: bool|null,
     *     resource: array<string, mixed>
     * }|null
     */
    private function eventEnvelope(array $payload, string $rawBody): ?array
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            return null;
        }

        $eventAttributes = $data['attributes'] ?? null;
        $legacyEnvelope = ($data['type'] ?? null) === 'event' && is_array($eventAttributes);

        $eventType = $legacyEnvelope
            ? ($eventAttributes['type'] ?? null)
            : ($data['type'] ?? null);
        $livemode = $legacyEnvelope
            ? ($eventAttributes['livemode'] ?? null)
            : ($data['livemode'] ?? null);
        $resource = $legacyEnvelope
            ? ($eventAttributes['data'] ?? null)
            : ($data['data'] ?? null);

        if (! is_string($eventType) || $eventType === '' || ! is_array($resource)) {
            return null;
        }

        $providerEventId = $data['id'] ?? $payload['id'] ?? null;
        $eventId = is_string($providerEventId) && str_starts_with($providerEventId, 'evt_')
            ? $providerEventId
            : 'payload_'.hash('sha256', $rawBody);

        return [
            'event_id' => mb_substr($eventId, 0, 160),
            'event_type' => mb_substr($eventType, 0, 120),
            'livemode' => is_bool($livemode) ? $livemode : null,
            'resource' => $resource,
        ];
    }

    /**
     * @param  array{
     *     event_id: string,
     *     event_type: string,
     *     livemode: bool,
     *     resource: array<string, mixed>
     * }  $envelope
     * @return array{status: string, http_status: int, payment_verified_application_id?: int}
     */
    private function processPaidCheckout(PaymongoWebhookEvent $event, array $envelope): array
    {
        $resource = $envelope['resource'];
        $attributes = $resource['attributes'] ?? null;
        $checkoutId = $resource['id'] ?? null;

        if (! is_array($attributes) || ! is_string($checkoutId) || ! str_starts_with($checkoutId, 'cs_')) {
            return $this->finishEvent($event, 'rejected', 'invalid_checkout_resource', 202);
        }

        $reference = $attributes['reference_number']
            ?? data_get($attributes, 'metadata.merchant_reference');

        if (! is_string($reference) || $reference === '') {
            return $this->finishEvent($event, 'rejected', 'missing_reference', 202);
        }

        // The attempt must match on both gateway session ID and our own reference.
        $attempt = PaymentAttempt::query()
            ->where('provider', 'paymongo')
            ->where('provider_checkout_id', $checkoutId)
            ->where('merchant_reference', $reference)
            ->lockForUpdate()
            ->first();

        if (! $attempt) {
            return $this->finishEvent($event, 'rejected', 'payment_attempt_not_found', 202);
        }

        $payment = $this->paidPaymentFrom($attributes);

        if (! $payment) {
            return $this->finishEvent($event, 'rejected', 'paid_payment_missing', 202);
        }

        $paymentAttributes = is_array($payment['attributes'] ?? null)
            ? $payment['attributes']
            : $payment;
        $paymentId = $payment['id'] ?? $paymentAttributes['id'] ?? null;
        $amount = $paymentAttributes['amount'] ?? null;
        $currency = strtoupper((string) ($paymentAttributes['currency'] ?? ''));

        if (! is_string($paymentId) || ! str_starts_with($paymentId, 'pay_')) {
            return $this->finishEvent($event, 'rejected', 'invalid_payment_id', 202);
        }

        if (! is_int($amount) || $amount !== $attempt->amount_minor) {
            return $this->finishEvent($event, 'rejected', 'amount_mismatch', 202);
        }

        if ($currency !== strtoupper($attempt->currency)) {
            return $this->finishEvent($event, 'rejected', 'currency_mismatch', 202);
        }

        if ($attempt->livemode !== $envelope['livemode']) {
            return $this->finishEvent($event, 'rejected', 'attempt_mode_mismatch', 202);
        }

        $application = EnrollmentApplication::query()
            ->whereKey($attempt->enrollment_application_id)
            ->lockForUpdate()
            ->first();

        if (! $application) {
            return $this->finishEvent($event, 'rejected', 'application_not_found', 202);
        }

        $metadataApplicationId = data_get($attributes, 'metadata.application_id');

        if (
            filled($metadataApplicationId)
            && (string) $metadataApplicationId !== (string) $application->getKey()
        ) {
            return $this->finishEvent($event, 'rejected', 'application_mismatch', 202);
        }

        $applicationAmountMinor = (int) round(((float) $application->payment_amount) * 100);

        if (
            $application->payment_method !== 'online'
            || $application->payment_reference !== $attempt->merchant_reference
            || $application->paymongo_checkout_reference !== $attempt->provider_checkout_id
            || strtoupper((string) $application->payment_currency) !== strtoupper($attempt->currency)
            || $applicationAmountMinor !== $attempt->amount_minor
        ) {
            return $this->finishEvent($event, 'rejected', 'application_payment_mismatch', 202);
        }

        $paymentIntentId = $paymentAttributes['payment_intent_id']
            ?? data_get($attributes, 'payment_intent.id');

        if ($attempt->status === PaymentAttempt::STATUS_PAID) {
            if (
                $attempt->provider_payment_id !== $paymentId
                || (
                    filled($attempt->provider_payment_intent_id)
                    && is_string($paymentIntentId)
                    && $attempt->provider_payment_intent_id !== $paymentIntentId
                )
            ) {
                return $this->finishEvent($event, 'rejected', 'conflicting_paid_payment', 202);
            }

            return $this->finishEvent($event, 'processed', null, 200);
        }

        $paidAt = now();

        $attempt->forceFill([
            'provider_payment_id' => $paymentId,
            'provider_payment_intent_id' => is_string($paymentIntentId) ? $paymentIntentId : null,
            'status' => PaymentAttempt::STATUS_PAID,
            'paid_at' => $paidAt,
            'meta' => array_merge($attempt->meta ?? [], [
                'verified_event_id' => $envelope['event_id'],
                'verified_at' => $paidAt->toIso8601String(),
            ]),
        ])->save();

        $amount = round($attempt->amount_minor / 100, 2);
        $transactionType = $amount >= (float) ($application->total_program_fee ?? 22000.00)
            ? PaymentTransaction::TYPE_FULL_PAYMENT
            : PaymentTransaction::TYPE_DOWNPAYMENT;

        PaymentTransaction::query()->updateOrCreate([
            'enrollment_application_id' => $application->id,
            'payment_channel' => PaymentTransaction::CHANNEL_ONLINE,
            'or_number' => $paymentId,
        ], [
            'user_id' => $application->user_id,
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'status' => PaymentTransaction::STATUS_VERIFIED,
            'paid_at' => $paidAt,
            'verified_at' => $paidAt,
            'notes' => 'Verified automatically from a signed PayMongo webhook.',
        ]);

        $application->forceFill([
            'payment_receipt_number' => $paymentId,
            'payment_verified_by_id' => null,
            'payment_verified_at' => $paidAt,
            'payment_verification_notes' => 'Verified automatically from a signed PayMongo webhook.',
            'payment_meta' => array_merge($application->payment_meta ?? [], [
                'gateway_verified' => true,
                'gateway' => 'paymongo',
                'paymongo_event_id' => $envelope['event_id'],
                'paymongo_payment_id' => $paymentId,
                'paymongo_payment_intent_id' => is_string($paymentIntentId) ? $paymentIntentId : null,
                'gateway_verified_at' => $paidAt->toIso8601String(),
            ]),
        ])->save();
        $application->recalculatePaymentStatus();

        AdminActivityLog::record(null, 'payment.paymongo.verified', $application, [
            'event_id' => $envelope['event_id'],
            'checkout_id' => $checkoutId,
            'payment_reference' => $attempt->merchant_reference,
            'amount_minor' => $attempt->amount_minor,
            'currency' => $attempt->currency,
            'livemode' => $attempt->livemode,
        ]);

        return [
            ...$this->finishEvent($event, 'processed', null, 200),
            'payment_verified_application_id' => $application->getKey(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>|null
     */
    private function paidPaymentFrom(array $attributes): ?array
    {
        $collections = [
            $attributes['payments'] ?? null,
            data_get($attributes, 'payment_intent.attributes.payments'),
            data_get($attributes, 'payment_intent.payments'),
        ];

        foreach ($collections as $payments) {
            if (! is_array($payments)) {
                continue;
            }

            foreach ($payments as $payment) {
                if (! is_array($payment)) {
                    continue;
                }

                $paymentAttributes = is_array($payment['attributes'] ?? null)
                    ? $payment['attributes']
                    : $payment;

                if (strtolower((string) ($paymentAttributes['status'] ?? '')) === 'paid') {
                    return $payment;
                }
            }
        }

        $singlePayment = $attributes['payment'] ?? null;

        if (is_array($singlePayment)) {
            $singleAttributes = is_array($singlePayment['attributes'] ?? null)
                ? $singlePayment['attributes']
                : $singlePayment;

            if (strtolower((string) ($singleAttributes['status'] ?? '')) === 'paid') {
                return $singlePayment;
            }
        }

        return null;
    }

    /**
     * @return array{status: string, http_status: int}
     */
    private function finishEvent(
        PaymongoWebhookEvent $event,
        string $status,
        ?string $errorCode,
        int $httpStatus,
    ): array {
        $event->forceFill([
            'status' => $status,
            'error_code' => $errorCode,
            'processed_at' => now(),
        ])->save();

        return [
            'status' => $status,
            'http_status' => $httpStatus,
        ];
    }
}
