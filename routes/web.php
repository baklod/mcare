<?php

use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminAnnouncementController;
use App\Http\Controllers\Admin\AdminCareerHubController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLearningSystemController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\BatchScheduleController;
use App\Http\Controllers\Admin\CertificationController;
use App\Http\Controllers\Admin\EnrollmentReviewController;
use App\Http\Controllers\Admin\PaymentScheduleController;
use App\Http\Controllers\Alumni\AlumniCareerHubController;
use App\Http\Controllers\Auth\AccountSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CompetencyWorkbookController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EnrollmentPaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Trainee\CertificateController as TraineeCertificateController;
use App\Http\Controllers\Trainee\QuizAttemptController as TraineeQuizAttemptController;
use App\Http\Controllers\Trainee\QuizController as TraineeQuizController;
use App\Http\Controllers\Trainee\TraineeDashboardController;
use App\Http\Controllers\Trainer\AnnouncementController as TrainerAnnouncementController;
use App\Http\Controllers\Trainer\CompetencyRecordController as TrainerCompetencyRecordController;
use App\Http\Controllers\Trainer\QuizController as TrainerQuizController;
use App\Http\Controllers\Trainer\TrainerDashboardController;
use App\Http\Controllers\Trainer\TrainerPortalController;
use App\Http\Controllers\Trainer\TrainingModuleController as TrainerTrainingModuleController;
use App\Models\EnrollmentApplication;
use Illuminate\Support\Facades\Route;

/*
 * Every web route receives a generous coarse limiter. This is NOT the main
 * defense against injection; it only slows abusive request floods. Sensitive
 * endpoints below add stricter limiters on top of this global baseline.
 */
Route::middleware('throttle:global-web')->group(function () {
    Route::get('/', function () {
        $applicationId = request()->session()->get('enrollment.payment_application_id');
        $applicationProgress = is_numeric($applicationId)
            ? EnrollmentApplication::query()->whereKey((int) $applicationId)->first()
            : null;

        return view('landing.home', compact('applicationProgress'));
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
        ->middleware(['throttle:admin-login', 'private.response'])
        ->name('login.store');

    Route::post('/login/verify-2fa', [AdminSessionController::class, 'verifyTwoFactor'])
        ->middleware(['throttle:admin-login', 'private.response'])
        ->name('login.verify-2fa');

    Route::post('/logout', [AccountSessionController::class, 'destroy'])
        ->middleware('throttle:sensitive-mutation')
        ->name('logout');

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1', 'private.response'])
        ->name('verification.verify');

    Route::prefix('account')
        ->name('account.')
        ->middleware(['auth', 'private.response'])
        ->group(function () {
            Route::get('/settings', [AccountSettingsController::class, 'show'])->name('settings');
            Route::get('/help', [AccountSettingsController::class, 'help'])->name('help');
            Route::patch('/password', [AccountSettingsController::class, 'updatePassword'])
                ->middleware('throttle:sensitive-mutation')
                ->name('password.update');
            Route::post('/security-event', [AccountSettingsController::class, 'securityEvent'])
                ->middleware('throttle:sensitive-mutation')
                ->name('security-event');
        });

    Route::prefix('notifications')
        ->name('notifications.')
        ->middleware(['auth', 'private.response'])
        ->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::patch('/{notification}/read', [NotificationController::class, 'markRead'])
                ->middleware('throttle:sensitive-mutation')
                ->name('read');
            Route::post('/read-all', [NotificationController::class, 'markAllRead'])
                ->middleware('throttle:sensitive-mutation')
                ->name('read-all');
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

        Route::get('/enrollment/drafts/{field}/content', [EnrollmentController::class, 'draftContent'])
            ->middleware('throttle:document-downloads')
            ->name('enrollment.drafts.content');

        Route::middleware(['enrollment.payment.access'])->group(function () {
            Route::get('/payment', [EnrollmentPaymentController::class, 'show'])
                ->name('payment.show');

            Route::post('/payment', [EnrollmentPaymentController::class, 'select'])
                ->middleware(['throttle:6,1'])
                ->name('payment.select');

            Route::get('/payment/return', [EnrollmentPaymentController::class, 'returned'])
                ->middleware(['throttle:20,1'])
                ->name('payment.return');

            Route::get('/payment/cancel', [EnrollmentPaymentController::class, 'cancelled'])
                ->middleware(['throttle:20,1'])
                ->name('payment.cancel');

            Route::get('/payment/status', [EnrollmentPaymentController::class, 'status'])
                ->middleware(['throttle:30,1'])
                ->name('payment.status');

            Route::get('/payment/complete', [EnrollmentPaymentController::class, 'completed'])
                ->middleware(['throttle:30,1'])
                ->name('payment.complete');

            Route::get('/payment/receipt', [EnrollmentPaymentController::class, 'receipt'])
                ->middleware(['throttle:20,1'])
                ->name('payment.receipt');

            Route::get('/payment/receipt/download', [EnrollmentPaymentController::class, 'downloadReceipt'])
                ->middleware(['throttle:document-downloads'])
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
            Route::get('/login', fn () => redirect()->route('login'))
                ->name('login');

            Route::middleware(['auth', 'admin', 'two-factor', 'permission:admin.access'])->group(function () {
                Route::get('/', AdminDashboardController::class)->name('dashboard');

                Route::get('/enrollments', [EnrollmentReviewController::class, 'index'])
                    ->middleware(['permission:enrollments.review', 'throttle:search'])
                    ->name('enrollments.index');

                Route::get('/enrollments/{enrollmentApplication}', [EnrollmentReviewController::class, 'show'])
                    ->middleware('permission:enrollments.review')
                    ->name('enrollments.show');

                Route::patch('/enrollments/{enrollmentApplication}', [EnrollmentReviewController::class, 'update'])
                    ->middleware(['permission:enrollments.review', 'throttle:sensitive-mutation'])
                    ->name('enrollments.update');

                Route::get('/enrollments/{enrollmentApplication}/tesda-form', [EnrollmentReviewController::class, 'tesdaForm'])
                    ->middleware(['permission:enrollments.review', 'throttle:document-downloads'])
                    ->name('enrollments.tesda-form');

                Route::patch('/enrollments/{enrollmentApplication}/documents/review', [EnrollmentReviewController::class, 'updateDocumentReview'])
                    ->middleware(['permission:enrollments.review', 'throttle:sensitive-mutation'])
                    ->name('enrollments.documents.review');

                Route::get('/enrollments/{enrollmentApplication}/documents/{document}', [EnrollmentReviewController::class, 'documentPreview'])
                    ->middleware(['permission:enrollments.review', 'throttle:document-downloads'])
                    ->name('enrollments.documents.show');

                Route::get('/enrollments/{enrollmentApplication}/documents/{document}/content', [EnrollmentReviewController::class, 'documentContent'])
                    ->middleware(['permission:enrollments.review', 'throttle:document-downloads'])
                    ->name('enrollments.documents.content');

                Route::get('/schedules', [BatchScheduleController::class, 'index'])
                    ->middleware('permission:schedules.manage')
                    ->name('schedules.index');

                Route::post('/schedules', [BatchScheduleController::class, 'store'])
                    ->middleware(['permission:schedules.manage', 'throttle:sensitive-mutation'])
                    ->name('schedules.store');

                Route::get('/schedules/{trainingBatch}/edit', [BatchScheduleController::class, 'edit'])
                    ->middleware('permission:schedules.manage')
                    ->name('schedules.edit');

                Route::patch('/schedules/{trainingBatch}', [BatchScheduleController::class, 'update'])
                    ->middleware(['permission:schedules.manage', 'throttle:sensitive-mutation'])
                    ->name('schedules.update');

                Route::delete('/schedules/{trainingBatch}', [BatchScheduleController::class, 'destroy'])
                    ->middleware(['permission:schedules.manage', 'throttle:sensitive-mutation'])
                    ->name('schedules.destroy');

                Route::get('/payment-scheduling', [PaymentScheduleController::class, 'index'])
                    ->middleware('permission:payments.verify')
                    ->name('payment-schedules.index');

                Route::patch('/payment-scheduling/{enrollmentApplication}', [PaymentScheduleController::class, 'update'])
                    ->middleware(['permission:payments.verify', 'throttle:sensitive-mutation'])
                    ->name('payment-schedules.update');

                Route::post('/payment-scheduling/{enrollmentApplication}/transactions', [PaymentScheduleController::class, 'storeTransaction'])
                    ->middleware(['permission:payments.verify', 'throttle:sensitive-mutation'])
                    ->name('payment-schedules.transactions.store');

                Route::patch('/payment-scheduling/transactions/{transaction}/verify', [PaymentScheduleController::class, 'verifyTransaction'])
                    ->middleware(['permission:payments.verify', 'throttle:sensitive-mutation'])
                    ->name('payment-schedules.transactions.verify');
                Route::get('/payment-scheduling/transactions/{transaction}/proof', [PaymentScheduleController::class, 'receiptProof'])
                    ->middleware(['permission:payments.verify', 'throttle:document-downloads'])
                    ->name('payment-schedules.transactions.proof');

                Route::get('/announcements', [AdminAnnouncementController::class, 'index'])
                    ->middleware('permission:announcements.view')
                    ->name('announcements.index');

                Route::post('/announcements', [AdminAnnouncementController::class, 'store'])
                    ->middleware(['permission:announcements.view', 'throttle:sensitive-mutation'])
                    ->name('announcements.store');

                Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])
                    ->middleware(['permission:announcements.view', 'throttle:sensitive-mutation'])
                    ->name('announcements.destroy');

                Route::get('/learning/trainees', [AdminLearningSystemController::class, 'trainees'])
                    ->middleware('permission:trainees.manage')
                    ->name('learning.trainees');
                Route::get('/learning/trainees/export', [AdminLearningSystemController::class, 'exportTrainees'])
                    ->middleware(['permission:reports.export', 'throttle:document-downloads'])
                    ->name('learning.trainees.export');
                Route::patch('/learning/trainees/{enrollmentApplication}/status', [AdminLearningSystemController::class, 'updateTraineeStatus'])
                    ->middleware(['permission:trainees.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.trainees.status');
                Route::get('/learning/modules', [AdminLearningSystemController::class, 'modules'])
                    ->middleware('permission:modules.manage')
                    ->name('learning.modules');
                Route::post('/learning/modules', [AdminLearningSystemController::class, 'storeModule'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.store');
                Route::delete('/learning/modules/{module}', [AdminLearningSystemController::class, 'destroyModule'])
                    ->middleware(['permission:modules.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.modules.destroy');
                Route::get('/learning/certificates', [CertificationController::class, 'index'])
                    ->middleware('permission:official-documents.manage')
                    ->name('learning.certificates');
                Route::get('/learning/competency-records/excel', [CompetencyWorkbookController::class, 'downloadForAdmin'])
                    ->middleware(['permission:reports.export', 'throttle:document-downloads'])
                    ->name('learning.competency-workbooks.download');
                Route::post('/learning/certificates/{enrollmentApplication}/{type}', [CertificationController::class, 'generate'])
                    ->middleware(['permission:official-documents.manage', 'throttle:sensitive-mutation'])
                    ->whereIn('type', ['cotc', 'tor'])
                    ->name('learning.documents.generate');
                Route::patch('/learning/official-documents/{officialDocument}/release', [CertificationController::class, 'release'])
                    ->middleware(['permission:official-documents.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.documents.release');
                Route::post('/learning/certificates/{enrollmentApplication}/{type}/reissue', [CertificationController::class, 'reissue'])
                    ->middleware(['permission:official-documents.manage', 'throttle:sensitive-mutation'])
                    ->whereIn('type', ['cotc', 'tor'])
                    ->name('learning.documents.reissue');
                Route::get('/learning/official-documents/{officialDocument}/download', [CertificationController::class, 'download'])
                    ->middleware(['permission:official-documents.manage', 'throttle:document-downloads'])
                    ->name('learning.documents.download');
                Route::post('/learning/batch-tor-exports', [CertificationController::class, 'requestBatchTorExport'])
                    ->middleware(['permission:official-documents.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.batch-exports.store');
                Route::get('/learning/batch-tor-exports/{batchDocumentExport}/download', [CertificationController::class, 'downloadBatchExport'])
                    ->middleware(['permission:official-documents.manage', 'throttle:document-downloads'])
                    ->name('learning.batch-exports.download');
                Route::get('/learning/alumni-jobs', [AdminCareerHubController::class, 'index'])
                    ->middleware('permission:alumni.jobs.manage')
                    ->name('learning.alumni-jobs');
                Route::get('/learning/alumni-jobs/preview', [AdminCareerHubController::class, 'preview'])
                    ->middleware('permission:alumni.jobs.manage')
                    ->name('learning.alumni-jobs.preview');
                Route::post('/learning/alumni-jobs', [AdminCareerHubController::class, 'store'])
                    ->middleware(['permission:alumni.jobs.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.alumni-jobs.store');
                Route::patch('/learning/alumni-jobs/{careerOpportunity}', [AdminCareerHubController::class, 'update'])
                    ->middleware(['permission:alumni.jobs.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.alumni-jobs.update');
                Route::delete('/learning/alumni-jobs/{careerOpportunity}', [AdminCareerHubController::class, 'destroy'])
                    ->middleware(['permission:alumni.jobs.manage', 'throttle:sensitive-mutation'])
                    ->name('learning.alumni-jobs.destroy');
                Route::get('/learning/reports', [AdminLearningSystemController::class, 'reports'])->name('learning.reports');

                Route::get('/accounts', [AdminAccountController::class, 'index'])
                    ->middleware('permission:accounts.manage')
                    ->name('accounts.index');
                Route::post('/accounts/trainers', [AdminAccountController::class, 'storeTrainer'])
                    ->middleware(['permission:accounts.manage', 'throttle:sensitive-mutation'])
                    ->name('accounts.trainers.store');
                Route::post('/accounts/trainees', [AdminAccountController::class, 'storeTrainee'])
                    ->middleware(['permission:accounts.manage', 'throttle:sensitive-mutation'])
                    ->name('accounts.trainees.store');
                Route::delete('/accounts/{user}', [AdminAccountController::class, 'destroy'])
                    ->middleware(['permission:accounts.manage', 'throttle:sensitive-mutation'])
                    ->whereNumber('user')
                    ->name('accounts.destroy');

                Route::get('/logs', [AdminActivityLogController::class, 'index'])
                    ->middleware(['permission:logs.view', 'throttle:search'])
                    ->name('logs.index');

                Route::get('/logs/print', [AdminActivityLogController::class, 'print'])
                    ->middleware(['permission:logs.view', 'throttle:document-downloads'])
                    ->name('logs.print');

                Route::get('/logs/export', [AdminActivityLogController::class, 'export'])
                    ->middleware(['permission:logs.view', 'permission:reports.export', 'throttle:document-downloads'])
                    ->name('logs.export');
            });
        });
    Route::prefix('trainer')
        ->name('trainer.')
        ->middleware('private.response')
        ->group(function () {
            Route::get('/login', fn () => redirect()->route('login'))
                ->name('login');

            Route::middleware(['auth', 'trainer', 'permission:trainer.access'])->group(function () {
                Route::get('/', TrainerDashboardController::class)
                    ->name('dashboard');

                Route::get('/stream', [TrainerAnnouncementController::class, 'index'])
                    ->middleware('permission:announcements.manage')
                    ->name('stream');
                Route::post('/announcements', [TrainerAnnouncementController::class, 'store'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('announcements.store');
                Route::patch('/announcements/{announcement}', [TrainerAnnouncementController::class, 'update'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('announcements.update');
                Route::delete('/announcements/{announcement}', [TrainerAnnouncementController::class, 'destroy'])
                    ->middleware(['permission:announcements.manage', 'throttle:sensitive-mutation'])
                    ->name('announcements.destroy');

                Route::get('/trainings', [TrainerPortalController::class, 'trainings'])->name('trainings');
                Route::get('/trainees', [TrainerPortalController::class, 'trainees'])
                    ->middleware('permission:trainees.view')
                    ->name('trainees');
                Route::get('/trainees/export', [TrainerPortalController::class, 'exportTrainees'])
                    ->middleware(['permission:trainees.export', 'throttle:document-downloads'])
                    ->name('trainees.export');
                Route::get('/competency-records', [TrainerCompetencyRecordController::class, 'index'])
                    ->middleware('permission:competencies.assess')
                    ->name('competencies.index');
                Route::get('/competency-records/batches/{trainingBatch}/{chart}', [TrainerCompetencyRecordController::class, 'chart'])
                    ->whereIn('chart', ['progress', 'achievement'])
                    ->middleware(['permission:competencies.assess', 'throttle:document-downloads'])
                    ->name('competencies.chart');
                Route::get('/competency-records/batches/{trainingBatch}/excel', [CompetencyWorkbookController::class, 'downloadForTrainer'])
                    ->middleware(['permission:trainees.export', 'throttle:document-downloads'])
                    ->name('competencies.export');
                Route::patch('/competency-records/bulk', [TrainerCompetencyRecordController::class, 'bulkUpdate'])
                    ->middleware(['permission:competencies.assess', 'throttle:sensitive-mutation'])
                    ->name('competencies.bulk-update');
                Route::get('/competency-records/{enrollmentApplication}', [TrainerCompetencyRecordController::class, 'edit'])
                    ->middleware('permission:competencies.assess')
                    ->name('competencies.edit');
                Route::patch('/competency-records/{enrollmentApplication}', [TrainerCompetencyRecordController::class, 'update'])
                    ->middleware(['permission:competencies.assess', 'throttle:sensitive-mutation'])
                    ->name('competencies.update');
                Route::get('/sessions', [TrainerPortalController::class, 'sessions'])->name('sessions');
                Route::get('/assessments', [TrainerQuizController::class, 'index'])
                    ->middleware('permission:quizzes.manage')
                    ->name('assessments');
                Route::get('/quizzes/create', [TrainerQuizController::class, 'create'])
                    ->middleware('permission:quizzes.manage')
                    ->name('quizzes.create');
                Route::post('/quizzes', [TrainerQuizController::class, 'store'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('quizzes.store');
                Route::get('/quizzes/{quiz}/edit', [TrainerQuizController::class, 'edit'])
                    ->middleware('permission:quizzes.manage')
                    ->name('quizzes.edit');
                Route::patch('/quizzes/{quiz}', [TrainerQuizController::class, 'update'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('quizzes.update');
                Route::patch('/quizzes/{quiz}/publication', [TrainerQuizController::class, 'publication'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('quizzes.publication');
                Route::delete('/quizzes/{quiz}', [TrainerQuizController::class, 'destroy'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('quizzes.destroy');
                Route::get('/quizzes/{quiz}/results', [TrainerQuizController::class, 'results'])
                    ->middleware(['permission:quizzes.manage', 'permission:grades.view'])
                    ->name('quizzes.results');
                Route::get('/resources', [TrainerPortalController::class, 'resources'])
                    ->middleware('permission:modules.publish')
                    ->name('resources');
                Route::get('/certificates', [TrainerPortalController::class, 'certificates'])->name('certificates');
                Route::get('/reports', [TrainerPortalController::class, 'reports'])->name('reports');

                Route::post('/modules', [TrainerTrainingModuleController::class, 'store'])
                    ->middleware(['permission:modules.publish', 'throttle:8,1'])
                    ->name('modules.store');
                Route::patch('/modules/{module}', [TrainerTrainingModuleController::class, 'update'])
                    ->middleware(['permission:modules.publish', 'throttle:sensitive-mutation'])
                    ->name('modules.update');
                Route::delete('/modules/{module}', [TrainerTrainingModuleController::class, 'destroy'])
                    ->middleware(['permission:modules.publish', 'throttle:sensitive-mutation'])
                    ->name('modules.destroy');

                Route::post('/modules/{module}/evaluations', [TrainerTrainingModuleController::class, 'evaluate'])
                    ->middleware(['permission:competencies.assess', 'throttle:sensitive-mutation'])
                    ->name('modules.evaluate');
                Route::post('/modules/{module}/quizzes', [TrainerTrainingModuleController::class, 'storeQuiz'])
                    ->middleware(['permission:quizzes.manage', 'throttle:sensitive-mutation'])
                    ->name('modules.quizzes.store');

                Route::get('/modules/{module}', [TrainerDashboardController::class, 'viewModule'])
                    ->middleware('permission:modules.publish')
                    ->name('modules.show');

                Route::get('/modules/{module}/content', [TrainerDashboardController::class, 'moduleContent'])
                    ->middleware(['permission:modules.publish', 'throttle:document-downloads'])
                    ->name('modules.content');
                Route::get('/modules/{module}/download', [TrainerDashboardController::class, 'moduleDownload'])
                    ->middleware(['permission:modules.publish', 'throttle:document-downloads'])
                    ->name('modules.download');
                Route::get('/modules/{module}/supplementary/{index}', [TrainerDashboardController::class, 'supplementaryDownload'])
                    ->middleware(['permission:modules.publish', 'throttle:document-downloads'])
                    ->whereNumber('index')
                    ->name('modules.supplementary.download');
                Route::delete('/modules/{module}/supplementary/{index}', [TrainerTrainingModuleController::class, 'destroySupplementary'])
                    ->middleware(['permission:modules.publish', 'throttle:sensitive-mutation'])
                    ->whereNumber('index')
                    ->name('modules.supplementary.destroy');
            });
        });

    Route::prefix('trainee')
        ->name('trainee.')
        ->middleware('private.response')
        ->group(function () {
            Route::get('/login', fn () => redirect()->route('login'))
                ->name('login');

            Route::middleware(['auth', 'trainee', 'permission:trainee.access'])->group(function () {
                Route::get('/', [TraineeDashboardController::class, 'index'])
                    ->name('dashboard');
                Route::get('/schedule', [TraineeDashboardController::class, 'schedule'])
                    ->name('schedule');
                Route::get('/documents', [TraineeDashboardController::class, 'documents'])
                    ->middleware('permission:documents.view')
                    ->name('documents');
                Route::get('/documents/cotc/{officialDocument}/download', [TraineeCertificateController::class, 'download'])
                    ->middleware(['permission:cotc.download', 'throttle:document-downloads'])
                    ->name('cotc.download');

                Route::middleware('active.training')->group(function () {
                    Route::get('/stream', [TraineeDashboardController::class, 'stream'])
                        ->middleware('permission:announcements.view')
                        ->name('stream');
                    Route::get('/modules', [TraineeDashboardController::class, 'modules'])
                        ->middleware('permission:modules.view')
                        ->name('modules.index');
                    Route::get('/payments', [TraineeDashboardController::class, 'payments'])
                        ->middleware('permission:payments.view')
                        ->name('payments');
                    Route::post('/payments/tickets', [TraineeDashboardController::class, 'generateOnsiteTicket'])
                        ->middleware(['permission:payments.view', 'throttle:sensitive-mutation'])
                        ->name('payments.tickets.store');
                    Route::post('/payments/upload-proof', [TraineeDashboardController::class, 'uploadPaymentProof'])
                        ->middleware(['permission:payments.view', 'throttle:sensitive-mutation'])
                        ->name('payments.upload-proof');
                    Route::get('/modules/{module}', [TraineeDashboardController::class, 'viewModule'])
                        ->middleware('permission:modules.view')
                        ->name('modules.show');
                    Route::get('/modules/{module}/content', [TraineeDashboardController::class, 'moduleContent'])
                        ->middleware(['permission:modules.view', 'throttle:document-downloads'])
                        ->name('modules.content');
                    Route::get('/modules/{module}/download', [TraineeDashboardController::class, 'moduleDownload'])
                        ->middleware(['permission:modules.view', 'throttle:document-downloads'])
                        ->name('modules.download');
                    Route::get('/modules/{module}/supplementary/{index}', [TraineeDashboardController::class, 'supplementaryDownload'])
                        ->middleware(['permission:modules.view', 'throttle:document-downloads'])
                        ->whereNumber('index')
                        ->name('modules.supplementary.download');
                    Route::patch('/modules/{module}/progress', [TraineeDashboardController::class, 'updateProgress'])
                        ->middleware(['permission:progress.update', 'throttle:sensitive-mutation'])
                        ->name('modules.progress');
                    Route::post('/modules/{module}/security-event', [TraineeDashboardController::class, 'securityEvent'])
                        ->middleware(['permission:modules.view', 'throttle:20,1'])
                        ->name('modules.security-event');
                    Route::get('/quizzes', [TraineeQuizController::class, 'index'])
                        ->middleware('permission:quizzes.take')
                        ->name('quizzes.index');
                    Route::get('/quizzes/{quiz}', [TraineeQuizController::class, 'show'])
                        ->middleware('permission:quizzes.take')
                        ->name('quizzes.show');
                    Route::post('/quizzes/{quiz}/attempts', [TraineeQuizController::class, 'start'])
                        ->middleware(['permission:quizzes.take', 'throttle:sensitive-mutation'])
                        ->name('quizzes.start');
                    Route::get('/quiz-attempts/{attempt}', [TraineeQuizAttemptController::class, 'show'])
                        ->middleware('permission:quizzes.take')
                        ->name('quiz-attempts.show');
                    Route::post('/quiz-attempts/{attempt}/submit', [TraineeQuizAttemptController::class, 'submit'])
                        ->middleware(['permission:quizzes.take', 'throttle:sensitive-mutation'])
                        ->name('quiz-attempts.submit');
                    Route::get('/quiz-attempts/{attempt}/result', [TraineeQuizAttemptController::class, 'result'])
                        ->middleware('permission:quizzes.take')
                        ->name('quiz-attempts.result');
                });

                Route::middleware(['graduate', 'permission:alumni.jobs.view'])->group(function () {
                    Route::get('/career-hub', [AlumniCareerHubController::class, 'index'])
                        ->name('career-hub');
                    Route::patch('/career-hub/availability', [AlumniCareerHubController::class, 'updateAvailability'])
                        ->middleware('throttle:sensitive-mutation')
                        ->name('career-hub.availability');
                });
            });
        });

    // Legacy URL aliases render the shared trainee-based Career Hub; they do not create a second portal.
    Route::get('/alumni', [AlumniCareerHubController::class, 'index'])
        ->middleware(['auth', 'trainee', 'graduate', 'permission:alumni.jobs.view', 'private.response'])
        ->name('alumni.dashboard');

    Route::patch('/alumni/availability', [AlumniCareerHubController::class, 'updateAvailability'])
        ->middleware(['auth', 'trainee', 'graduate', 'permission:alumni.jobs.view', 'throttle:sensitive-mutation', 'private.response'])
        ->name('alumni.availability.update');
});
