<?php

use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'trainer' => \App\Http\Middleware\EnsureTrainer::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => match (true) {
            $request->is('admin') || $request->is('admin/*') => route('admin.login'),
            $request->is('trainer') || $request->is('trainer/*') => route('trainer.login'),
            default => route('login'),
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
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
                        default => 'Something went wrong. Please try again later.',
                    },
                ], $status);
            }

            if (config('app.debug') && $status === 500) {
                return null;
            }

            return response()->view('errors.universal', [
                'status' => $status,
                'title' => match ($status) {
                    403 => 'Access denied',
                    404 => 'Page not found',
                    419 => 'Session expired',
                    default => 'Something went wrong',
                },
                'message' => match ($status) {
                    403 => 'You do not have permission to open this page.',
                    404 => 'The page or record you requested could not be found.',
                    419 => 'Your session has expired. Please refresh the page and try again.',
                    default => 'The system could not complete the request. Please try again later.',
                },
            ], $status);
        });
    })->create();
