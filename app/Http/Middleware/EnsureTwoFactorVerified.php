<?php

namespace App\Http\Middleware;

use App\Services\EmailTwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && app(EmailTwoFactorService::class)->enabledFor($user)
            && (int) $request->session()->get('admin.mfa.verified_user_id') !== (int) $user->getAuthIdentifier()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Please complete email verification before entering the admin area.']);
        }

        return $next($request);
    }
}
