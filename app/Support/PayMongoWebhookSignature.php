<?php

namespace App\Support;

use Illuminate\Support\Str;

class PayMongoWebhookSignature
{
    public function configured(): bool
    {
        return filled($this->secret()) && Str::startsWith($this->secret(), 'whsk_');
    }

    public function verify(string $rawBody, ?string $header): bool
    {
        if (! $this->configured() || ! is_string($header) || $header === '') {
            return false;
        }

        $parts = [];

        foreach (explode(',', $header) as $component) {
            [$key, $value] = array_pad(explode('=', trim($component), 2), 2, null);

            if (is_string($key) && is_string($value) && $key !== '') {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['t'] ?? null;
        $signatureKey = (bool) config('services.paymongo.live_mode', false) ? 'li' : 'te';
        $providedSignature = $parts[$signatureKey] ?? null;

        if (
            ! is_string($timestamp)
            || ! ctype_digit($timestamp)
            || ! is_string($providedSignature)
            || ! preg_match('/^[a-f0-9]{64}$/i', $providedSignature)
        ) {
            return false;
        }

        $tolerance = max(60, min(3600, (int) config('services.paymongo.webhook_tolerance', 300)));

        if (abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $timestamp.'.'.$rawBody,
            $this->secret(),
        );

        return hash_equals($expected, strtolower($providedSignature));
    }

    private function secret(): string
    {
        return trim((string) config('services.paymongo.webhook_secret'));
    }
}
