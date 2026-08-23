<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use App\Services\EmailTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class AdminSessionController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        // Already-authenticated admins should land on the operations dashboard.
        if ($request->user()?->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('login');
    }

    public function store(Request $request): RedirectResponse
    {
        /*
         * Rate limiting slows repeated attempts, while these max lengths stop
         * a single request from sending unnecessarily huge credential values.
         */
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
        ]);

        // Normalize email consistently with the admin-login rate-limiter key.
        $credentials['email'] = Str::lower(trim($credentials['email']));

        // Validate the password without creating an authenticated session.
        // The privileged session is only created after the email code passes.
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], (string) $user->password)) {
            AdminActivityLog::record(null, 'admin.login.failed', null, [
                'email' => $credentials['email'],
            ]);

            return back()
                ->withErrors(['email' => 'The provided admin credentials are invalid.'])
                ->onlyInput('email');
        }

        // Reject non-admin accounts even when their email and password are valid.
        if (! $user->hasRole('admin')) {
            AdminActivityLog::record($user, 'admin.login.rejected', $user, [
                'email' => $user->email,
                'role' => $user->role,
            ]);

            return back()
                ->withErrors(['email' => 'This account is not allowed to access the admin area.'])
                ->onlyInput('email');
        }

        $twoFactor = app(EmailTwoFactorService::class);

        if ($user instanceof User && $twoFactor->enabledFor($user)) {
            try {
                $challenge = $twoFactor->issue($user);
                $challenge['remember'] = $request->boolean('remember');
                $request->session()->put('admin.mfa.pending', [
                    'user_id' => $user->id,
                    ...$challenge,
                ]);

                // Rotate the guest session before showing the challenge.
                $request->session()->regenerate();

                AdminActivityLog::record($user, 'admin.login.mfa.sent', $user);

                return redirect()
                    ->route('login')
                    ->with('mfa_notice', 'A verification code was sent to your staff email address.');
            } catch (Throwable $exception) {
                report($exception);
                $request->session()->forget('admin.mfa.pending');
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => 'We could not send a verification code. Please try again or contact an administrator.'])
                    ->onlyInput('email');
            }
        }

        $request->session()->regenerate();
        Auth::login($user, $request->boolean('remember'));
        AdminActivityLog::record($user, 'admin.login.success', $user);

        // Begin every privileged session at the dashboard instead of replaying
        // a stale intended URL from an earlier protected-page visit.
        return redirect()->route('admin.dashboard');
    }

    public function verifyTwoFactor(Request $request, EmailTwoFactorService $twoFactor): RedirectResponse
    {
        $challenge = $request->session()->get('admin.mfa.pending');

        if (! is_array($challenge) || ! isset($challenge['user_id'])) {
            return redirect()
                ->route('login')
                ->withErrors(['code' => 'Your verification session has expired. Please sign in again.']);
        }

        $pendingUser = User::query()->find($challenge['user_id']);
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if ($twoFactor->isExpired($challenge)) {
            $request->session()->forget('admin.mfa.pending');
            AdminActivityLog::record($pendingUser, 'admin.login.mfa.expired', $pendingUser);

            return redirect()
                ->route('login')
                ->with('mfa_notice', 'That verification code has expired. Please sign in again.')
                ->withErrors(['email' => 'Enter your staff email and password to request a new code.']);
        }

        $attempts = (int) ($challenge['attempts'] ?? 0);

        if ($attempts >= $twoFactor->maxAttempts()) {
            $request->session()->forget('admin.mfa.pending');
            AdminActivityLog::record($pendingUser, 'admin.login.mfa.locked', $pendingUser);

            return redirect()
                ->route('login')
                ->with('mfa_notice', 'Too many incorrect codes. Please sign in again.')
                ->withErrors(['email' => 'Enter your staff email and password to request a new code.']);
        }

        $challenge['attempts'] = $attempts + 1;
        $request->session()->put('admin.mfa.pending', $challenge);

        if (! $twoFactor->verify($challenge, $validated['code'])) {
            if ($challenge['attempts'] >= $twoFactor->maxAttempts()) {
                $request->session()->forget('admin.mfa.pending');
                AdminActivityLog::record($pendingUser, 'admin.login.mfa.locked', $pendingUser);
            } else {
                AdminActivityLog::record($pendingUser, 'admin.login.mfa.failed', $pendingUser);
            }

            return redirect()
                ->route('login')
                ->withErrors(['code' => 'The verification code is incorrect.']);
        }

        $user = $pendingUser;

        if (! $user?->hasRole('admin')) {
            $request->session()->forget('admin.mfa.pending');

            return redirect()
                ->route('login')
                ->withErrors(['code' => 'This staff account is no longer allowed to access the admin area.']);
        }

        $remember = (bool) ($challenge['remember'] ?? false);
        $request->session()->forget('admin.mfa.pending');
        $request->session()->regenerate();
        Auth::login($user, $remember);
        $request->session()->put('admin.mfa.verified_user_id', $user->id);

        AdminActivityLog::record($user, 'admin.login.mfa.verified', $user);

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        AdminActivityLog::record($request->user(), 'admin.logout', $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('landing')
            ->with('signed_out', 'You have signed out. You can now switch accounts safely.');
    }
}
