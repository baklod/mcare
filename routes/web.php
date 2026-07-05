<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EnrollmentPaymentController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\BatchScheduleController;
use App\Http\Controllers\Admin\EnrollmentReviewController;
use App\Http\Controllers\Admin\PaymentScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing.home');
})->name('landing');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/login', fn () => redirect()->route('enrollment.create'))->name('login');
Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');

Route::get('/enrollment', [EnrollmentController::class, 'create'])->name('enrollment.create');
Route::post('/enrollment', [EnrollmentController::class, 'store'])->middleware('throttle:3,1')->name('enrollment.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/payment', [EnrollmentPaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment', [EnrollmentPaymentController::class, 'select'])->middleware('throttle:6,1')->name('payment.select');
    Route::get('/payment/receipt', [EnrollmentPaymentController::class, 'receipt'])->middleware('throttle:20,1')->name('payment.receipt');
    Route::get('/payment/receipt/download', [EnrollmentPaymentController::class, 'downloadReceipt'])->middleware('throttle:10,1')->name('payment.receipt.download');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
    Route::post('/login', [AdminSessionController::class, 'store'])->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('logout');
        Route::redirect('/', '/admin/enrollments')->name('dashboard');
        Route::get('/enrollments', [EnrollmentReviewController::class, 'index'])->name('enrollments.index');
        Route::get('/enrollments/{enrollmentApplication}', [EnrollmentReviewController::class, 'show'])->name('enrollments.show');
        Route::patch('/enrollments/{enrollmentApplication}', [EnrollmentReviewController::class, 'update'])->name('enrollments.update');
        Route::get('/enrollments/{enrollmentApplication}/documents/{document}', [EnrollmentReviewController::class, 'document'])->name('enrollments.documents.show');
        Route::get('/schedules', [BatchScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/schedules', [BatchScheduleController::class, 'store'])->middleware('throttle:10,1')->name('schedules.store');
        Route::get('/schedules/{trainingBatch}/edit', [BatchScheduleController::class, 'edit'])->name('schedules.edit');
        Route::patch('/schedules/{trainingBatch}', [BatchScheduleController::class, 'update'])->middleware('throttle:10,1')->name('schedules.update');
        Route::delete('/schedules/{trainingBatch}', [BatchScheduleController::class, 'destroy'])->middleware('throttle:6,1')->name('schedules.destroy');
        Route::get('/payment-scheduling', [PaymentScheduleController::class, 'index'])->name('payment-schedules.index');
        Route::get('/logs', [AdminActivityLogController::class, 'index'])->name('logs.index');
    });
});
