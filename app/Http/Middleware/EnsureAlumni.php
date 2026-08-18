<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAlumni
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Both checks fail closed if the legacy role and Spatie assignment drift apart.
        if ($user?->role !== 'alumni' || ! $user->hasRole('alumni')) {
            abort(403);
        }

        return $next($request);
    }
}
