<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        // Already-authenticated admins should land on the operations dashboard.
        if (request()->user()?->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            AdminActivityLog::record(null, 'admin.login.failed', null, [
                'email' => $credentials['email'],
            ]);

            return back()
                ->withErrors(['email' => 'The provided admin credentials are invalid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // Reject non-admin accounts even when their email and password are valid.
        if ($request->user()?->role !== 'admin') {
            AdminActivityLog::record($request->user(), 'admin.login.rejected', $request->user(), [
                'email' => $request->user()?->email,
                'role' => $request->user()?->role,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'This account is not allowed to access the admin area.'])
                ->onlyInput('email');
        }

        AdminActivityLog::record($request->user(), 'admin.login.success', $request->user());

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AdminActivityLog::record($request->user(), 'admin.logout', $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
