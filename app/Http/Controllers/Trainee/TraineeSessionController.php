<?php

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TraineeSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()?->hasRole('trainee')) {
            return redirect()->route('trainee.dashboard');
        }

        return view('trainee.auth.login', [
            // Makes role testing clear when another account is already active in this browser.
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
            AdminActivityLog::record(null, 'trainee.login.failed', null, [
                'email' => $credentials['email'],
            ]);

            return back()
                ->withErrors(['email' => 'The provided trainee credentials are invalid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if (! $request->user()?->hasRole('trainee')) {
            AdminActivityLog::record($request->user(), 'trainee.login.rejected', $request->user(), [
                'email' => $request->user()?->email,
                'role' => $request->user()?->role,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'This account is not approved for the trainee dashboard yet.'])
                ->onlyInput('email');
        }

        AdminActivityLog::record($request->user(), 'trainee.login.success', $request->user());

        return redirect()->intended(route('trainee.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AdminActivityLog::record($request->user(), 'trainee.logout', $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('trainee.login');
    }
}
