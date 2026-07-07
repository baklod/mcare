<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainer
{
    public function handle(Request $request, Closure $next): Response
    {
        // This guard is intentionally role-column based until Spatie Permission is migrated in.
        if ($request->user()?->role !== 'trainer') {
            abort(403);
        }

        return $next($request);
    }
}
