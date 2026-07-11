<?php

use App\Http\Controllers\Auth\AccountSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EnrollmentPaymentController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\BatchScheduleController;
use App\Http\Controllers\Admin\EnrollmentReviewController;
use App\Http\Controllers\Admin\PaymentScheduleController;
use App\Http\Controllers\Trainer\TrainerDashboardController;
use App\Http\Controllers\Trainer\TrainerSessionController;
use App\Http\Controllers\Trainee\TraineeDashboardController;
use App\Http\Controllers\Trainee\TraineeSessionController;
use Illuminate\Support\Facades\Route;

/*
 * Every web route receives a generous coarse limiter. This is NOT the main
 * defense against injection; it only slows abusive request floods. Sensitive
 * endpoints below add stricter limiters on top of this global baseline.
 */
Route::middleware('throttle:global-web')->group(function () {
    Route::get('/', function () {
        return view('landing.home');
    })->name('landing');

    /*
     * OAuth callback URLs can temporarily contain authorization parameters.
     * Mark both directions no-store/noindex in addition to rate limiting them.
     */
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
        ->middleware(['throttle:oauth', 'private.response'])
        ->name('auth.google.redirect');

    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware(['throttle:oauth', 'private.response'])
        ->name('auth.google.callback');

    Route::get('/login', [AccountSessionController::class, 'create'])
        ->middleware('private.response')
        ->name('login');

    Route::post('/login', [AccountSessionController::class, 'store'])
        ->middleware(['throttle:8,1', 'private.response'])
        ->name('login.store');

    Route::post('/logout', [AccountSessionController::class, 'destroy'])
        ->middleware('throttle:sensitive-mutation')
        ->name('logout');

    /*
     * Enrollment can display a signed-in applicant's saved profile, so it gets
     * no-cache/no-index response headers even though the form is publicly reachable.
     */
    Route::middleware('private.response')->group(function () {
        Route::get('/enrollment', [EnrollmentController::class, 'create'])
            ->name('enrollment.create');

        Route::post('/enrollment', [EnrollmentController::class, 'store'])
            ->middleware('throttle:3,1')
            ->name('enrollment.store');

        Route::middleware(['auth'])->group(function () {
            Route::get('/payment', [EnrollmentPaymentController::class, 'show'])
                ->name('payment.show');

            Route::post('/payment', [EnrollmentPaymentController::class, 'select'])
                ->middleware('throttle:6,1')
                ->name('payment.select');

            Route::get('/payment/receipt', [EnrollmentPaymentController::class, 'receipt'])
                ->middleware('throttle:20,1')
                ->name('payment.receipt');

            Route::get('/payment/receipt/download', [EnrollmentPaymentController::class, 'downloadReceipt'])
                ->middleware('throttle:document-downloads')
                ->name('payment.receipt.download');
        });
    });

    /*
     * The entire admin area receives privacy headers. Authentication and role
     * checks remain the real access control; noindex/robots headers are only
     * additional privacy and search-engine hygiene.
     */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('private.response')
        ->group(function () {
            Route::get('/login', [AdminSessionController::class, 'create'])
                ->name('login');

            Route::post('/login', [AdminSessionController::class, 'store'])
                ->middleware('throttle:admin-login')
                ->name('login.store');

            Route::middleware(['auth', 'admin'])->group(function () {
                Route::post('/logout', [AdminSessionController::class, 'destroy'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('logout');

                Route::get('/', AdminDashboardController::class)->name('dashboard');

                Route::get('/enrollments', [EnrollmentReviewController::class, 'index'])
                    ->middleware('throttle:search')
                    ->name('enrollments.index');

                Route::get('/enrollments/{enrollmentApplication}', [EnrollmentReviewController::class, 'show'])
                    ->name('enrollments.show');

                Route::patch('/enrollments/{enrollmentApplication}', [EnrollmentReviewController::class, 'update'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('enrollments.update');

                Route::get('/enrollments/{enrollmentApplication}/documents/{document}', [EnrollmentReviewController::class, 'document'])
                    ->middleware('throttle:document-downloads')
                    ->name('enrollments.documents.show');

                Route::get('/schedules', [BatchScheduleController::class, 'index'])
                    ->name('schedules.index');

                Route::post('/schedules', [BatchScheduleController::class, 'store'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('schedules.store');

                Route::get('/schedules/{trainingBatch}/edit', [BatchScheduleController::class, 'edit'])
                    ->name('schedules.edit');

                Route::patch('/schedules/{trainingBatch}', [BatchScheduleController::class, 'update'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('schedules.update');

                Route::delete('/schedules/{trainingBatch}', [BatchScheduleController::class, 'destroy'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('schedules.destroy');

                Route::get('/payment-scheduling', [PaymentScheduleController::class, 'index'])
                    ->name('payment-schedules.index');

                Route::get('/logs', [AdminActivityLogController::class, 'index'])
                    ->middleware('throttle:search')
                    ->name('logs.index');
            });
        });
    Route::prefix('trainer')
        ->name('trainer.')
        ->middleware('private.response')
        ->group(function () {
            Route::get('/login', [TrainerSessionController::class, 'create'])
                ->name('login');

            Route::post('/login', [TrainerSessionController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('login.store');

            Route::middleware(['auth', 'trainer'])->group(function () {
                Route::post('/logout', [TrainerSessionController::class, 'destroy'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('logout');

                Route::get('/', TrainerDashboardController::class)
                    ->name('dashboard');

                Route::post('/modules', [TrainerDashboardController::class, 'storeModule'])
                    ->middleware('throttle:8,1')
                    ->name('modules.store');

                Route::get('/modules/{module}/download', [TrainerDashboardController::class, 'downloadModule'])
                    ->middleware('throttle:document-downloads')
                    ->name('modules.download');
            });
        });

    Route::prefix('trainee')
        ->name('trainee.')
        ->middleware('private.response')
        ->group(function () {
            Route::get('/login', [TraineeSessionController::class, 'create'])
                ->name('login');

            Route::post('/login', [TraineeSessionController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('login.store');

            Route::middleware(['auth', 'trainee'])->group(function () {
                Route::post('/logout', [TraineeSessionController::class, 'destroy'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('logout');

                Route::get('/', [TraineeDashboardController::class, 'index'])
                    ->name('dashboard');

                Route::get('/modules/{module}/download', [TraineeDashboardController::class, 'downloadModule'])
                    ->middleware('throttle:document-downloads')
                    ->name('modules.download');
            });
        });
});
