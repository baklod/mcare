<?php

namespace App\Services;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailTwoFactorService
{
    /** @return array{hash: string, expires_at: int, attempts: int} */
    public function issue(User $user): array
    {
        $code = $this->generateCode();
        $expiresInMinutes = $this->ttlMinutes();

        Mail::to($user->email)->send(new TwoFactorCodeMail(
            recipientName: $user->name ?: 'MCARE staff member',
            code: $code,
            expiresInMinutes: $expiresInMinutes,
        ));

        return [
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($expiresInMinutes)->timestamp,
            'attempts' => 0,
        ];
    }

    public function enabledFor(User $user): bool
    {
        $roles = config('services.two_factor.roles', ['admin']);

        return (bool) config('services.two_factor.enabled', true)
            && in_array((string) $user->role, $roles, true)
            && filled($user->email);
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('services.two_factor.max_attempts', 5));
    }

    public function ttlMinutes(): int
    {
        return max(1, (int) config('services.two_factor.ttl', 10));
    }

    public function verify(array $challenge, string $code): bool
    {
        return isset($challenge['hash']) && Hash::check($code, (string) $challenge['hash']);
    }

    public function isExpired(array $challenge): bool
    {
        return ! isset($challenge['expires_at']) || now()->timestamp >= (int) $challenge['expires_at'];
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
