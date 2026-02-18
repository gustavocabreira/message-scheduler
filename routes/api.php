<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HuggyOAuthController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ScheduledMessageController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');

        // Huggy OAuth2
        Route::get('/huggy/redirect', [HuggyOAuthController::class, 'redirect'])->name('auth.huggy.redirect');
        Route::get('/huggy/callback', [HuggyOAuthController::class, 'callback'])->name('auth.huggy.callback');
    });
});

// Provider routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('providers', ProviderController::class);
    Route::post('providers/{provider}/test-connection', [ProviderController::class, 'testConnection'])
        ->name('providers.test-connection');
    Route::get('providers/{provider}/contacts', [ProviderController::class, 'contacts'])
        ->name('providers.contacts');

    // Scheduled Messages
    Route::apiResource('scheduled-messages', ScheduledMessageController::class);
    Route::get('scheduled-messages/{scheduledMessage}/logs', [ScheduledMessageController::class, 'logs'])
        ->name('scheduled-messages.logs');
});
