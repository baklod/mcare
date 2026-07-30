<?php

use App\Http\Controllers\PayMongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/paymongo/webhook', PayMongoWebhookController::class)
    ->middleware('throttle:paymongo-webhooks')
    ->name('paymongo.webhook');

Route::prefix('v1')
    ->middleware('auth')
    ->group(function () {
        Route::get('/dashboard/summary', [\App\Http\Controllers\Api\V1\MobileDashboardApiController::class, 'summary'])
            ->name('api.v1.dashboard.summary');
    });
