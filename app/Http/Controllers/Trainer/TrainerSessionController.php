<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainerSessionController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        if ($request->user()?->hasRole('trainer')) {
            return redirect()->route('trainer.dashboard');
        }

        return redirect()->route('login');
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

        if (! $request->user()?->hasRole('trainer')) {
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

        return redirect()
            ->route('landing')
            ->with('signed_out', 'You have signed out. You can now switch accounts safely.');
    }
}
