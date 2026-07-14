<?php

namespace App\Http\Middleware;

use App\Models\EnrollmentApplication;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorize payment continuation for either an authenticated applicant or
 * the short-lived session created by a just-completed enrollment form.
 *
 * The anonymous branch is intentionally session-bound: no application id is
 * accepted from the URL or request body, and the controller still resolves
 * the record through the same server-side session key.
 */
class EnsureEnrollmentPaymentAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            abort_unless($request->user()->can('payments.view'), 403);

            return $next($request);
        }

        $applicationId = $request->session()->get('enrollment.payment_application_id');
        $application = is_numeric($applicationId)
            ? EnrollmentApplication::query()->whereKey((int) $applicationId)->first()
            : null;

        abort_unless($application, 403);

        return $next($request);
    }
}
