<?php

use App\Http\Controllers\PayMongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/paymongo/webhook', PayMongoWebhookController::class)
    ->middleware('throttle:paymongo-webhooks')
    ->name('paymongo.webhook');
