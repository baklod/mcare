<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole('trainer')) {
            abort(403);
        }

        return $next($request);
    }
}
