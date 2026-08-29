<?php

namespace App\Services;

use App\Exceptions\PayMongoCheckoutException;
use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayMongoCheckoutService
{
    private const CHECKOUT_ENDPOINT = 'https://api.paymongo.com/v2/checkout_sessions';

    public function secretConfigured(): bool
    {
        $secret = $this->secretKey();
        $expectedPrefix = $this->isLiveMode() ? 'sk_live_' : 'sk_test_';

        return filled($secret) && Str::startsWith($secret, $expectedPrefix);
    }

    public function webhookConfigured(): bool
    {
        $secret = trim((string) config('services.paymongo.webhook_secret'));

        return filled($secret) && Str::startsWith($secret, 'whsk_');
    }

    public function ready(): bool
    {
        return $this->secretConfigured() && $this->webhookConfigured();
    }

    public function isLiveMode(): bool
    {
        return (bool) config('services.paymongo.live_mode', false);
    }

    /**
     * @return list<string>
     */
    public function enabledMethods(): array
    {
        $configured = config('services.paymongo.payment_methods', ['gcash', 'card', 'qrph']);
        $methods = is_array($configured) ? $configured : explode(',', (string) $configured);

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $method): string => strtolower(trim((string) $method)),
            $methods,
        ), static fn (string $method): bool => (bool) preg_match('/^[a-z0-9_]{2,40}$/', $method))));
    }

    /**
     * @return array{checkout_id: string, checkout_url: string, livemode: bool}
     *
     * @throws PayMongoCheckoutException
     */
    public function createCheckout(PaymentAttempt $attempt): array
    {
        if (! $this->secretConfigured()) {
            throw new PayMongoCheckoutException('PayMongo checkout is not configured.');
        }

        if ($attempt->livemode !== $this->isLiveMode()) {
            throw new PayMongoCheckoutException('PayMongo checkout mode does not match the payment attempt.');
        }

        $payload = data_get($attempt->meta, 'checkout_payload');

        if (! is_array($payload)) {
            throw new PayMongoCheckoutException('The PayMongo checkout request is incomplete.');
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withBasicAuth($this->secretKey(), '')
                ->withHeaders([
                    'Idempotency-Key' => $attempt->idempotency_key,
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->post(self::CHECKOUT_ENDPOINT, $payload);
        } catch (ConnectionException) {
            throw new PayMongoCheckoutException(
                'PayMongo could not be reached.',
                retryable: true,
            );
        }

        if ($response->failed()) {
            $status = $response->status();

            throw new PayMongoCheckoutException(
                'PayMongo rejected checkout creation.',
                retryable: in_array($status, [409, 429], true) || $status >= 500,
                responseStatus: $status,
            );
        }

        $checkoutId = $response->json('data.id');
        $checkoutUrl = $response->json('data.attributes.checkout_url');
        $livemode = $response->json('data.attributes.livemode');

        if (
            ! is_string($checkoutId)
            || ! Str::startsWith($checkoutId, 'cs_')
            || ! is_string($checkoutUrl)
            || ! $this->isTrustedCheckoutUrl($checkoutUrl)
            || ! is_bool($livemode)
            || $livemode !== $this->isLiveMode()
        ) {
            throw new PayMongoCheckoutException('PayMongo returned an invalid checkout response.');
        }

        return [
            'checkout_id' => $checkoutId,
            'checkout_url' => $checkoutUrl,
            'livemode' => $livemode,
        ];
    }

    /**
     * Build once, persist on the attempt, and reuse verbatim with the same
     * idempotency key even if batch/config values change after a timeout.
     *
     * @return array<string, mixed>
     */
    public function buildCheckoutPayload(
        PaymentAttempt $attempt,
        EnrollmentApplication $application,
    ): array {
        $methods = $this->enabledMethods();

        if ($methods === []) {
            throw new PayMongoCheckoutException('No PayMongo payment methods are configured.');
        }

        return [
            'data' => [
                'attributes' => [
                    'line_items' => [[
                        'name' => 'MCARE '.($application->program ?: 'training program').' downpayment',
                        'description' => 'Enrollment downpayment for Mission Care Training Center',
                        'amount' => $attempt->amount_minor,
                        'currency' => $attempt->currency,
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => $methods,
                    'success_url' => route('payment.return'),
                    'cancel_url' => route('payment.cancel'),
                    'reference_number' => $attempt->merchant_reference,
                    'description' => 'MCARE enrollment downpayment',
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'show_line_items' => true,
                    'pass_on_fees' => false,
                    // Keep gateway metadata minimal and free of uploaded-document data.
                    'metadata' => [
                        'application_id' => (string) $application->getKey(),
                        'merchant_reference' => (string) $attempt->merchant_reference,
                        'batch_id' => (string) ($application->training_batch_id ?? ''),
                    ],
                ],
            ],
        ];
    }

    public function isTrustedCheckoutUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 'checkout.paymongo.com'
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && (! isset($parts['port']) || $parts['port'] === 443);
    }

    private function secretKey(): string
    {
        return trim((string) config('services.paymongo.secret_key'));
    }
}
