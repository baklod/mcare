<?php

use App\Console\Commands\PrepareDemoGraduate;
use App\Http\Middleware\EnsureActiveTraining;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureEnrollmentPaymentAccess;
use App\Http\Middleware\EnsureGraduate;
use App\Http\Middleware\EnsureTrainee;
use App\Http\Middleware\EnsureTrainer;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Http\Middleware\PrivateResponseHeaders;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        PrepareDemoGraduate::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * SecurityHeaders runs for every response. This gives the whole app a
         * common browser-security baseline instead of relying on each page.
         */
        $middleware->append(SecurityHeaders::class);

        // Preserve ngrok/reverse-proxy HTTPS and host information when the
        // proxy is explicitly trusted (loopback-only by default).
        $trustedProxies = array_values(array_filter(array_map(
            static fn (string $proxy): string => trim($proxy),
            explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))
        )));
        $middleware->trustProxies(at: $trustedProxies);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'active.training' => EnsureActiveTraining::class,
            'enrollment.payment.access' => EnsureEnrollmentPaymentAccess::class,
            'graduate' => EnsureGraduate::class,
            'private.response' => PrivateResponseHeaders::class,
            'permission' => PermissionMiddleware::class,
            'trainer' => EnsureTrainer::class,
            'trainee' => EnsureTrainee::class,
            'two-factor' => EnsureTwoFactorVerified::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            // Let Laravel keep its normal redirect/JSON behavior for these two
            // common framework exceptions. Everything else gets a safe wrapper.
            if ($e instanceof AuthenticationException || $e instanceof ValidationException) {
                return null;
            }

            $status = match (true) {
                $e instanceof TokenMismatchException => 419,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => match ($status) {
                        403 => 'You are not allowed to access this resource.',
                        404 => 'The requested resource was not found.',
                        419 => 'Your session expired. Please refresh and try again.',
                        429 => 'Too many requests. Please wait before trying again.',
                        default => 'Something went wrong. Please try again later.',
                    },
                ], $status);
            }

            /*
             * Detailed exceptions are useful only on a truly local machine.
             * Even if APP_DEBUG is accidentally left true in production, this
             * condition keeps the public 500 response generic.
             */
            if (app()->isLocal() && config('app.debug') && $status === 500) {
                return null;
            }

            return response()->view('errors.universal', [
                'status' => $status,
                'title' => match ($status) {
                    403 => 'Access denied',
                    404 => 'Page not found',
                    419 => 'Session expired',
                    429 => 'Too many requests',
                    default => 'Something went wrong',
                },
                'message' => match ($status) {
                    403 => 'You do not have permission to open this page.',
                    404 => 'The page or record you requested could not be found.',
                    419 => 'Your session has expired. Please refresh the page and try again.',
                    429 => 'Too many requests were sent in a short time. Please wait and try again.',
                    default => 'The system could not complete the request. Please try again later.',
                },
            ], $status);
        });
    })->create();
