<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainee
{
    public function handle(Request $request, Closure $next): Response
    {
        // Approved applicants are promoted to this role by the admin review flow.
        if ($request->user()?->role !== 'trainee') {
            abort(403);
        }

        return $next($request);
    }
}
