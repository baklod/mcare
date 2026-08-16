<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use App\Services\EmailTwoFactorService;
use App\Support\AccountPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\View\View;

class AccountSessionController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.login', [
            // Shows the currently active browser session so role testing is not confusing.
            'activeUser' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], (string) $user->password)) {
            AdminActivityLog::record(null, 'account.login.failed', null, [
                'email' => $credentials['email'],
            ]);

            return back()
                ->withErrors(['email' => 'The provided account credentials are invalid.'])
                ->onlyInput('email');
        }

        // Keep administrator accounts on the dedicated staff challenge flow so
        // the public login form cannot become a second-factor bypass.
        $twoFactor = app(EmailTwoFactorService::class);

        if ($user->hasRole('admin') && $twoFactor->enabledFor($user)) {
            try {
                $challenge = $twoFactor->issue($user);
                $challenge['remember'] = $request->boolean('remember');
                $request->session()->put('admin.mfa.pending', [
                    'user_id' => $user->id,
                    ...$challenge,
                ]);
                $request->session()->regenerate();

                AdminActivityLog::record($user, 'admin.login.mfa.sent', $user, [
                    'source' => 'account-login',
                ]);

                return redirect()
                    ->route('admin.login')
                    ->with('mfa_notice', 'A verification code was sent to your staff email address.');
            } catch (Throwable $exception) {
                report($exception);

                return back()
                    ->withErrors(['email' => 'We could not send a verification code. Please try again or contact an administrator.'])
                    ->onlyInput('email');
            }
        }

        // Regenerate after any successful login or account switch to prevent session fixation.
        $request->session()->regenerate();
        Auth::login($user, $request->boolean('remember'));

        AdminActivityLog::record($request->user(), 'account.login.success', $request->user(), [
            'role' => $request->user()?->role,
        ]);

        // Admin sessions always start from the operations dashboard. A stale
        // intended URL must not send a newly signed-in admin into a submodule.
        if ($request->user()?->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route(AccountPortal::routeNameFor($request->user())));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AdminActivityLog::record($request->user(), 'account.logout', $request->user(), [
            'role' => $request->user()?->role,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('landing')
            ->with('signed_out', 'You have signed out. You can now switch accounts safely.');
    }
}
