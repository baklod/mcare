<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainee
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isCurrentTrainee = $user?->hasRole('trainee');
        $isLegacyGraduate = $user?->hasRole('alumni') && $user->isGraduate();

        // Legacy alumni are accepted only when a real graduated enrollment
        // exists; both account types then use the same trainee portal.
        if (! $isCurrentTrainee && ! $isLegacyGraduate) {
            abort(403);
        }

        return $next($request);
    }
}
