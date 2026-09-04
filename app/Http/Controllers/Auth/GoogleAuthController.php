<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\HistoricalAlumniClaim;
use App\Models\User;
use App\Services\AnnouncementDeliveryService;
use App\Services\ProfilePhotoStore;
use App\Support\AccountPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $redirectUri = $this->oauthRedirectUri();

        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            AdminActivityLog::record($request->user(), 'account.login.google.failed', $request->user(), [
                'reason' => 'provider_not_configured',
            ]);

            return redirect()
                ->route('landing')
                ->with('google_config_missing', 'Google OAuth is installed. Add GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI to .env to enable sign in.');
        }

        if (! $this->hasSafeRedirectUri($redirectUri)) {
            AdminActivityLog::record($request->user(), 'account.login.google.failed', $request->user(), [
                'reason' => 'unsafe_callback_configuration',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'Google sign in is temporarily unavailable because the secure callback URL is not configured correctly. Please use email sign in or contact the administrator.');
        }

        $canonicalStart = $this->canonicalOauthStartUrl($request);
        if ($canonicalStart !== null) {
            return redirect()->away($canonicalStart);
        }

        AdminActivityLog::record($request->user(), 'account.login.google.started', $request->user());

        return $this->googleDriver($request)->redirect();
    }

    public function callback(
        Request $request,
        AnnouncementDeliveryService $announcementDelivery,
        ProfilePhotoStore $profilePhotos,
    ): RedirectResponse {
        if ($request->filled('error')) {
            $providerError = (string) $request->query('error');

            AdminActivityLog::record($request->user(), 'account.login.google.failed', $request->user(), [
                'reason' => $providerError === 'access_denied' ? 'provider_cancelled' : 'provider_returned_error',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', $providerError === 'access_denied'
                    ? 'Google sign in was cancelled. Please try again when you are ready.'
                    : 'Google sign in could not be completed. Please try again.');
        }

        $redirectUri = $this->oauthRedirectUri();

        if (! $this->hasSafeRedirectUri($redirectUri)) {
            AdminActivityLog::record($request->user(), 'account.login.google.failed', $request->user(), [
                'reason' => 'unsafe_callback_configuration',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'Google sign in is temporarily unavailable because the secure callback URL is not configured correctly. Please use email sign in or contact the administrator.');
        }

        try {
            $googleUser = $this->googleDriver($request)->user();
        } catch (InvalidStateException $exception) {
            report($exception);

            AdminActivityLog::record($request->user(), 'account.login.google.failed', $request->user(), [
                'reason' => 'oauth_state_mismatch',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'Google sign in expired. Open this site at the same browser address and try again.');
        } catch (Throwable $exception) {
            report($exception);

            AdminActivityLog::record($request->user(), 'account.login.google.failed', $request->user(), [
                'reason' => 'oauth_callback_failed',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'Google sign in could not be completed. Please try again.');
        }

        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            AdminActivityLog::record($request->user(), 'account.login.google.failed', $request->user(), [
                'reason' => 'email_unavailable',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'Google did not provide a usable email address. Please choose another Google account.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            AdminActivityLog::record(null, 'account.login.google.rejected', null, [
                'reason' => 'enrollment_required',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'No MCARE enrollment is registered for that Google email. Submit an application first, complete enrollment after approval, then return and use the same email to connect Google.')
                ->with('enrollment_required', true);
        }

        if (in_array($user->role, ['admin', 'trainer'], true)) {
            AdminActivityLog::record($user, 'account.login.google.rejected', $user, [
                'role' => $user->role,
                'reason' => 'staff_password_required',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'Staff accounts must use email and password on the MCARE sign-in page.');
        }

        $googleId = trim((string) $googleUser->getId());
        if ($googleId === '') {
            AdminActivityLog::record($user, 'account.login.google.rejected', $user, [
                'role' => $user->role,
                'reason' => 'provider_identity_unavailable',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'Google did not provide a usable account identity. Please choose another Google account or use email and password.');
        }

        $googleIdOwner = User::query()
            ->where('google_id', $googleId)
            ->whereKeyNot($user->getKey())
            ->first();
        $identityChanged = filled($user->google_id) && ! hash_equals((string) $user->google_id, $googleId);

        if ($googleIdOwner || $identityChanged) {
            AdminActivityLog::record($user, 'account.login.google.rejected', $user, [
                'role' => $user->role,
                'reason' => $googleIdOwner ? 'provider_identity_already_linked' : 'provider_identity_mismatch',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'This MCARE account is already connected to a different Google identity. Use email and password or contact the administrator before changing the connection.');
        }

        $displayName = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: $user->name));

        $user->forceFill([
            'google_id' => $googleId,
            ...($profilePhotos->isManaged($user) ? [] : [
                'avatar_url' => $googleUser->getAvatar(),
            ]),
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        $application = $user->enrollmentApplication()->latest()->first();
        $historicalClaim = $user->historicalAlumniClaim()->first();

        if ($user->role === 'applicant' && $historicalClaim) {
            if ($historicalClaim->status === HistoricalAlumniClaim::STATUS_PENDING_EMAIL) {
                $historicalClaim->forceFill([
                    'status' => HistoricalAlumniClaim::STATUS_PENDING_ONSITE,
                ])->save();
                $user->forceFill([
                    'applicant_status' => 'historical_claim_pending_onsite',
                ])->save();
            }

            $request->session()->regenerate();

            AdminActivityLog::record($user, 'account.google.verification.completed', $user, [
                'role' => $user->role,
                'outcome' => 'historical_claim_pending_onsite',
            ]);

            return redirect()
                ->route('login')
                ->with('verified', 'Google verified your email and profile picture. Your alumni account remains locked until MCARE checks your valid ID, original COTC/TOR, and archive record onsite.');
        }

        if ($user->role === 'applicant' && $application?->status === EnrollmentApplication::STATUS_DENIED) {
            Auth::login($user, true);
            $request->session()->forget('enrollment.payment_application_id');
            $request->session()->forget('enrollment.awaiting_approval');
            $request->session()->regenerate();

            $this->recordSuccessfulGoogleLogin(
                $user,
                $announcementDelivery,
                'denied_application_reentry',
            );

            return redirect()
                ->route('enrollment.create')
                ->with('reapply_notice', 'Your previous application was not approved. Correct the highlighted information or documents below, then resubmit using this same Google account.');
        }

        if ($user->role === 'applicant' && $application?->hasEnrollmentPaymentClearance()) {
            Auth::logout();
            $request->session()->put('enrollment.payment_application_id', $application->id);
            $request->session()->put('enrollment.awaiting_approval', true);
            $request->session()->regenerate();

            AdminActivityLog::record($user, 'account.login.google.rejected', $user, [
                'role' => $user->role,
                'reason' => 'awaiting_admin_approval',
            ]);

            return redirect()
                ->route('landing')
                ->with('account_pending', 'Your payment is verified. Please wait for the administrator to approve your MCARE account. We will email you when login is available.');
        }

        $request->session()->put(
            'enrollment.google_identity',
            $this->enrollmentIdentity($googleUser, $displayName, $email),
        );

        Auth::login($user, true);
        $request->session()->forget('enrollment.awaiting_approval');
        $request->session()->regenerate();

        $this->recordSuccessfulGoogleLogin($user, $announcementDelivery, 'portal_login');

        return redirect()
            ->route(AccountPortal::routeNameFor($user))
            ->with('signed_in', $user->enrollmentApplication()->exists()
                ? 'Google account verified. Welcome back to MCARE.'
                : 'Google account verified. Your name and email are ready for enrollment.');
    }

    public function logout(): RedirectResponse
    {
        AdminActivityLog::record(request()->user(), 'account.logout.google', request()->user(), [
            'role' => request()->user()?->role,
        ]);

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('landing');
    }

    private function enrollmentIdentity(object $googleUser, string $displayName, string $email): array
    {
        $raw = method_exists($googleUser, 'getRaw') && is_array($googleUser->getRaw())
            ? $googleUser->getRaw()
            : [];
        $firstName = trim((string) ($raw['given_name'] ?? ''));
        $middleName = '';
        $lastName = trim((string) ($raw['family_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            $parts = preg_split('/\s+/u', $displayName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = $firstName ?: (string) array_shift($parts);
            $lastName = $lastName ?: (count($parts) > 0 ? (string) array_pop($parts) : '');
            $middleName = implode(' ', $parts);
        }

        return [
            'email' => $email,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'full_name' => $displayName,
            'avatar_url' => $googleUser->getAvatar(),
        ];
    }

    private function googleDriver(Request $request): Provider
    {
        config(['services.google.redirect' => $this->oauthRedirectUri()]);

        return Socialite::driver('google');
    }

    private function oauthRedirectUri(): string
    {
        return (string) config('services.google.redirect');
    }

    private function canonicalOauthStartUrl(Request $request): ?string
    {
        $configured = parse_url($this->oauthRedirectUri());
        if (! is_array($configured) || empty($configured['scheme']) || empty($configured['host'])) {
            return null;
        }

        $configuredHost = $this->normalizedHost((string) $configured['host']);
        $requestHost = $this->normalizedHost((string) $request->getHost());

        if ($configuredHost === $requestHost) {
            return null;
        }

        if (! $this->isLoopbackHost($configuredHost) || ! $this->isLoopbackHost($requestHost)) {
            return null;
        }

        $scheme = $request->getScheme();
        $origin = $scheme.'://'.$configured['host'];
        $port = (int) $request->getPort();
        $defaultPort = strtolower($scheme) === 'https' ? 443 : 80;

        if ($port > 0 && $port !== $defaultPort) {
            $origin .= ':'.$port;
        }

        return $origin.'/auth/google';
    }

    private function hasSafeRedirectUri(string $redirectUri): bool
    {
        $redirect = parse_url($redirectUri);
        $app = parse_url((string) config('app.url'));

        if (! is_array($redirect) || ! isset($redirect['scheme'], $redirect['host'])) {
            return false;
        }

        $redirectHost = $this->normalizedHost((string) $redirect['host']);
        $appHost = $this->normalizedHost((string) ($app['host'] ?? ''));
        $isLocal = $this->isLoopbackHost($redirectHost);
        $secureScheme = strtolower((string) $redirect['scheme']) === 'https'
            || ($isLocal && strtolower((string) $redirect['scheme']) === 'http');
        $matchingHost = $appHost !== '' && (
            $redirectHost === $appHost
            || ($isLocal && $this->isLoopbackHost($appHost))
        );
        $callbackPath = $this->normalizedPath((string) ($redirect['path'] ?? '')) === '/auth/google/callback';

        return $secureScheme && $matchingHost && $callbackPath;
    }

    private function normalizedHost(string $host): string
    {
        $host = strtolower(trim($host, '[]'));

        return $host === '::1' ? '127.0.0.1' : $host;
    }

    private function isLoopbackHost(string $host): bool
    {
        return in_array($this->normalizedHost($host), ['localhost', '127.0.0.1'], true);
    }

    private function normalizedPath(string $path): string
    {
        return '/'.trim($path, '/');
    }

    private function recordSuccessfulGoogleLogin(
        User $user,
        AnnouncementDeliveryService $announcementDelivery,
        string $flow,
    ): void {
        $catchUpCount = $announcementDelivery->catchUpFor($user);

        AdminActivityLog::record($user, 'account.login.google.success', $user, [
            'role' => $user->role,
            'flow' => $flow,
            'announcement_catch_up_count' => $catchUpCount,
        ]);
    }
}
