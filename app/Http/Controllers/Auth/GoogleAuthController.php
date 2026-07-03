<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()
                ->route('landing')
                ->with('google_config_missing', 'Google OAuth is installed. Add GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI to .env to enable sign in.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable) {
            return redirect()
                ->route('landing')
                ->with('auth_error', 'Google sign in could not be completed. Please try again.');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->forceFill([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'MCARE Applicant',
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'MCARE Applicant',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'role' => 'applicant',
                'applicant_status' => 'oauth_verified',
                'email_verified_at' => now(),
                'password' => Str::password(40),
            ]);
        }

        Auth::login($user, true);

        return redirect()
            ->route('landing')
            ->with('signed_in', 'Google account verified. You can now begin your enrollment application.');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
