<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TrainerSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        // Already-authenticated trainers should go straight to their teaching dashboard.
        if (request()->user()?->role === 'trainer') {
            return redirect()->route('trainer.dashboard');
        }

        return view('trainer.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The provided trainer credentials are invalid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // Reject valid accounts that are not assigned to the trainer role.
        if ($request->user()?->role !== 'trainer') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'This account is not allowed to access the trainer area.'])
                ->onlyInput('email');
        }

        return redirect()->intended(route('trainer.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('trainer.login');
    }
}
