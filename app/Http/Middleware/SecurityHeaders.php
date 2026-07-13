<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add a security baseline to every HTTP response.
 *
 * Important: headers are defense-in-depth. They do not replace validation,
 * authorization, CSRF protection, or safe database queries.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent browsers from guessing a different MIME type than the server sent.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // MCARE pages are not designed to be embedded in iframes. This reduces
        // clickjacking risk, where a malicious site overlays our buttons invisibly.
        $response->headers->set(
            'X-Frame-Options',
            $request->routeIs(
                'trainer.modules.content',
                'trainee.modules.content',
                'admin.enrollments.documents.content'
            ) ? 'SAMEORIGIN' : 'DENY'
        );

        /*
         * Use a safe public default, but do not overwrite a stricter policy set
         * by PrivateResponseHeaders (for example, `no-referrer` on PII pages).
         */
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        // Disable browser capabilities that the current application does not need.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Legacy Adobe cross-domain policy files should not grant access to this app.
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if (app()->isProduction()) {
            /*
             * Current Blade pages contain inline JavaScript (for example, the
             * signature pad), so 'unsafe-inline' is temporarily required here.
             * A future hardening step should move inline scripts to Vite bundles
             * and replace this with nonces or hashes.
             */
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; "
                . "img-src 'self' data: https:; font-src 'self' data:; "
                . "style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; "
                . "connect-src 'self' https:; upgrade-insecure-requests"
            );

            // Only advertise HSTS when the current production request is really HTTPS.
            // Enabling HSTS on plain HTTP would lock browsers into a broken setup.
            if ($request->isSecure()) {
                $response->headers->set(
                    'Strict-Transport-Security',
                    'max-age=31536000; includeSubDomains'
                );
            }
        }

        return $response;
    }
}
