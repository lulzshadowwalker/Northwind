<?php

use App\Http\Controllers\TabbyWebhookController;
use Illuminate\Support\Facades\Route;

// Tabby webhook route (no CSRF protection needed for API routes)
Route::post('/webhooks/tabby', [TabbyWebhookController::class, 'handle'])
    ->name('api.webhooks.tabby');
