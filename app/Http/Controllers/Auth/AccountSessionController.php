<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Support\AccountPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            AdminActivityLog::record(null, 'account.login.failed', null, [
                'email' => $credentials['email'],
            ]);

            return back()
                ->withErrors(['email' => 'The provided account credentials are invalid.'])
                ->onlyInput('email');
        }

        // Regenerate after any successful login or account switch to prevent session fixation.
        $request->session()->regenerate();

        AdminActivityLog::record($request->user(), 'account.login.success', $request->user(), [
            'role' => $request->user()?->role,
        ]);

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
