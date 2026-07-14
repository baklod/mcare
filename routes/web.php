<?php

use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLearningSystemController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\BatchScheduleController;
use App\Http\Controllers\Admin\EnrollmentReviewController;
use App\Http\Controllers\Admin\PaymentScheduleController;
use App\Http\Controllers\Auth\AccountSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EnrollmentPaymentController;
use App\Http\Controllers\Trainee\TraineeDashboardController;
use App\Http\Controllers\Trainee\TraineeSessionController;
use App\Http\Controllers\Trainer\TrainerDashboardController;
use App\Http\Controllers\Trainer\TrainerPortalController;
use App\Http\Controllers\Trainer\TrainerSessionController;
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

    Route::prefix('account')
        ->name('account.')
        ->middleware(['auth', 'private.response'])
        ->group(function () {
            Route::get('/settings', [AccountSettingsController::class, 'show'])->name('settings');
            Route::get('/help', [AccountSettingsController::class, 'help'])->name('help');
            Route::patch('/password', [AccountSettingsController::class, 'updatePassword'])
                ->middleware('throttle:sensitive-mutation')
                ->name('password.update');
        });

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

                Route::get('/enrollments/{enrollmentApplication}/tesda-form', [EnrollmentReviewController::class, 'tesdaForm'])
                    ->middleware('throttle:document-downloads')
                    ->name('enrollments.tesda-form');

                Route::patch('/enrollments/{enrollmentApplication}/documents/review', [EnrollmentReviewController::class, 'updateDocumentReview'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('enrollments.documents.review');

                Route::get('/enrollments/{enrollmentApplication}/documents/{document}', [EnrollmentReviewController::class, 'documentPreview'])
                    ->middleware('throttle:document-downloads')
                    ->name('enrollments.documents.show');

                Route::get('/enrollments/{enrollmentApplication}/documents/{document}/content', [EnrollmentReviewController::class, 'documentContent'])
                    ->middleware('throttle:document-downloads')
                    ->name('enrollments.documents.content');

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

                Route::patch('/payment-scheduling/{enrollmentApplication}', [PaymentScheduleController::class, 'update'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('payment-schedules.update');

                Route::get('/learning/trainees', [AdminLearningSystemController::class, 'trainees'])->name('learning.trainees');
                Route::get('/learning/trainees/export', [AdminLearningSystemController::class, 'exportTrainees'])
                    ->middleware('throttle:document-downloads')
                    ->name('learning.trainees.export');
                Route::patch('/learning/trainees/{enrollmentApplication}/status', [AdminLearningSystemController::class, 'updateTraineeStatus'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('learning.trainees.status');
                Route::get('/learning/modules', [AdminLearningSystemController::class, 'modules'])->name('learning.modules');
                Route::post('/learning/modules', [AdminLearningSystemController::class, 'storeModule'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('learning.modules.store');
                Route::delete('/learning/modules/{module}', [AdminLearningSystemController::class, 'destroyModule'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('learning.modules.destroy');
                Route::get('/learning/certificates', [AdminLearningSystemController::class, 'certificates'])->name('learning.certificates');
                Route::get('/learning/alumni-jobs', [AdminLearningSystemController::class, 'alumniJobs'])->name('learning.alumni-jobs');
                Route::get('/learning/reports', [AdminLearningSystemController::class, 'reports'])->name('learning.reports');

                Route::get('/accounts', [AdminAccountController::class, 'index'])->name('accounts.index');
                Route::post('/accounts/trainers', [AdminAccountController::class, 'storeTrainer'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('accounts.trainers.store');
                Route::post('/accounts/trainees', [AdminAccountController::class, 'storeTrainee'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('accounts.trainees.store');

                Route::get('/logs', [AdminActivityLogController::class, 'index'])
                    ->middleware('throttle:search')
                    ->name('logs.index');

                Route::get('/logs/print', [AdminActivityLogController::class, 'print'])
                    ->middleware('throttle:document-downloads')
                    ->name('logs.print');

                Route::get('/logs/export', [AdminActivityLogController::class, 'export'])
                    ->middleware('throttle:document-downloads')
                    ->name('logs.export');
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

                Route::get('/trainings', [TrainerPortalController::class, 'trainings'])->name('trainings');
                Route::get('/trainees', [TrainerPortalController::class, 'trainees'])->name('trainees');
                Route::get('/trainees/export', [TrainerPortalController::class, 'exportTrainees'])
                    ->middleware('throttle:document-downloads')
                    ->name('trainees.export');
                Route::get('/sessions', [TrainerPortalController::class, 'sessions'])->name('sessions');
                Route::get('/assessments', [TrainerPortalController::class, 'assessments'])->name('assessments');
                Route::get('/resources', [TrainerPortalController::class, 'resources'])->name('resources');
                Route::get('/certificates', [TrainerPortalController::class, 'certificates'])->name('certificates');
                Route::get('/reports', [TrainerPortalController::class, 'reports'])->name('reports');

                Route::post('/modules', [TrainerDashboardController::class, 'storeModule'])
                    ->middleware('throttle:8,1')
                    ->name('modules.store');

                Route::get('/modules/{module}', [TrainerDashboardController::class, 'viewModule'])
                    ->name('modules.show');

                Route::get('/modules/{module}/content', [TrainerDashboardController::class, 'moduleContent'])
                    ->middleware('throttle:document-downloads')
                    ->name('modules.content');
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

                Route::get('/modules', [TraineeDashboardController::class, 'modules'])
                    ->name('modules.index');
                Route::get('/schedule', [TraineeDashboardController::class, 'schedule'])
                    ->name('schedule');
                Route::get('/payments', [TraineeDashboardController::class, 'payments'])
                    ->name('payments');
                Route::get('/documents', [TraineeDashboardController::class, 'documents'])
                    ->name('documents');

                Route::get('/modules/{module}', [TraineeDashboardController::class, 'viewModule'])
                    ->name('modules.show');

                Route::get('/modules/{module}/content', [TraineeDashboardController::class, 'moduleContent'])
                    ->middleware('throttle:document-downloads')
                    ->name('modules.content');

                Route::patch('/modules/{module}/progress', [TraineeDashboardController::class, 'updateProgress'])
                    ->middleware('throttle:sensitive-mutation')
                    ->name('modules.progress');

                Route::post('/modules/{module}/security-event', [TraineeDashboardController::class, 'securityEvent'])
                    ->middleware('throttle:20,1')
                    ->name('modules.security-event');
            });
        });
});
