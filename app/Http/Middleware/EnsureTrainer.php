<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainer
{
    public function handle(Request $request, Closure $next): Response
    {
        // Trainer pages must stay separate from admin, applicant, trainee, and alumni sessions.
        if ($request->user()?->role !== 'trainer') {
            abort(403);
        }

        return $next($request);
    }
}
