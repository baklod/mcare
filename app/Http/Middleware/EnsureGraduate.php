<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGraduate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            ($user?->hasRole('trainee') || $user?->hasRole('alumni')) && $user->isGraduate(),
            403
        );

        return $next($request);
    }
}
