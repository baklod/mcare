<?php

use App\Http\Controllers\Api\V1\MobileDashboardApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('auth')
    ->group(function () {
        Route::get('/dashboard/summary', [MobileDashboardApiController::class, 'summary'])
            ->name('api.v1.dashboard.summary');
    });
