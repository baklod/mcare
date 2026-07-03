<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\EnrollmentReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing.home');
})->name('landing');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/login', fn () => redirect()->route('enrollment.create'))->name('login');
Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');

Route::get('/enrollment', [EnrollmentController::class, 'create'])->name('enrollment.create');
Route::post('/enrollment', [EnrollmentController::class, 'store'])->name('enrollment.store');

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
    });
});
