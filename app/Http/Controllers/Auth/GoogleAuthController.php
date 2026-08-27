<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\HistoricalAlumniClaim;
use App\Models\User;
use App\Services\AnnouncementDeliveryService;
use App\Support\AccountPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $redirectUri = (string) config('services.google.redirect');

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

        /*
         * Keep Socialite stateful for this normal browser/session application.
         * Socialite stores an OAuth `state` value in the session and checks it
         * on callback, which helps defend against login CSRF and response mixups.
         */
        AdminActivityLog::record($request->user(), 'account.login.google.started', $request->user());

        return Socialite::driver('google')->redirect();
    }

    public function callback(
        Request $request,
        AnnouncementDeliveryService $announcementDelivery,
    ): RedirectResponse {
        try {
            /*
             * Do NOT call stateless() here. The application already uses web
             * sessions, so preserving Socialite's state validation is safer.
             */
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
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

        // Staff accounts must complete password and challenge verification.
        // Applicant OAuth must not become a privileged-session bypass.
        if ($user && in_array($user->role, ['admin', 'trainer'], true)) {
            AdminActivityLog::record($user, 'account.login.google.rejected', $user, [
                'role' => $user->role,
                'reason' => 'staff_password_required',
            ]);

            return redirect()
                ->route('landing')
                ->with('auth_error', 'Staff accounts must use email and password on the MCARE sign-in page.');
        }

        $displayName = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: 'MCARE Applicant'));

        if ($user) {
            $user->forceFill([
                'name' => $displayName,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ])->save();
        } else {
            $user = new User;
            $user->forceFill([
                'name' => $displayName,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'role' => 'applicant',
                'applicant_status' => 'oauth_verified',
                'email_verified_at' => now(),
                'password' => Str::password(40),
            ])->save();
        }

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

        // Rotate the session ID after authentication to reduce fixation risk.
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

    /** @return array{email: string, first_name: string, middle_name: string, last_name: string, full_name: string, avatar_url: ?string} */
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

    private function hasSafeRedirectUri(string $redirectUri): bool
    {
        $redirect = parse_url($redirectUri);
        $app = parse_url((string) config('app.url'));

        if (! is_array($redirect) || ! isset($redirect['scheme'], $redirect['host'])) {
            return false;
        }

        $isLocal = in_array(strtolower((string) $redirect['host']), ['localhost', '127.0.0.1', '::1'], true);
        $secureScheme = strtolower((string) $redirect['scheme']) === 'https' || ($isLocal && strtolower((string) $redirect['scheme']) === 'http');
        $matchingHost = isset($app['host']) && strtolower((string) $redirect['host']) === strtolower((string) $app['host']);
        $callbackPath = rtrim((string) ($redirect['path'] ?? ''), '/') === '/auth/google/callback';

        return $secureScheme && $matchingHost && $callbackPath;
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
