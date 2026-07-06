<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protect pages that may contain applicant, payment, or administrative data.
 *
 * This middleware does two jobs:
 * 1. asks browsers/proxies not to keep a reusable cached copy; and
 * 2. asks search engines not to index, archive, or snippet the response.
 *
 * These headers are privacy controls, not authorization controls. The route
 * must still use auth/role middleware where access should be restricted.
 */
class PrivateResponseHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Do not leave sensitive pages in shared caches or normal browser caches.
        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, private, max-age=0'
        );
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        // HTTP header form of <meta name="robots">. This also covers downloads
        // and non-HTML responses where a meta tag cannot be used.
        $response->headers->set(
            'X-Robots-Tag',
            'noindex, nofollow, noarchive, nosnippet'
        );

        // Private pages may contain PII in filters or paths. Do not send their
        // URL as a Referer header when navigating away from MCARE.
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
