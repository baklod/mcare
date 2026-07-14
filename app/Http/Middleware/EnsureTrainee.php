<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainee
{
    public function handle(Request $request, Closure $next): Response
    {
        // Approved applicants receive this Spatie role from the review flow.
        if (! $request->user()?->hasRole('trainee')) {
            abort(403);
        }

        return $next($request);
    }
}
