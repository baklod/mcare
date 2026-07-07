<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TrainerSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()?->role === 'trainer') {
            return redirect()->route('trainer.dashboard');
        }

        return view('trainer.auth.login', [
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
            AdminActivityLog::record(null, 'trainer.login.failed', null, [
                'email' => $credentials['email'],
            ]);

            return back()
                ->withErrors(['email' => 'The provided trainer credentials are invalid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($request->user()?->role !== 'trainer') {
            AdminActivityLog::record($request->user(), 'trainer.login.rejected', $request->user(), [
                'email' => $request->user()?->email,
                'role' => $request->user()?->role,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'This account is not allowed to access the trainer area.'])
                ->onlyInput('email');
        }

        AdminActivityLog::record($request->user(), 'trainer.login.success', $request->user());

        return redirect()->intended(route('trainer.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AdminActivityLog::record($request->user(), 'trainer.logout', $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('trainer.login');
    }
}
