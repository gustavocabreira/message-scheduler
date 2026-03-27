<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Auth\Http\Controllers\AuthController;
use Src\Auth\Http\Controllers\MeController;
use Src\Tenant\Http\Controllers\TenantController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('me', MeController::class)->name('me');

    Route::get('v1/workspaces', [TenantController::class, 'index'])->name('workspaces.index');

    Route::prefix('v1/workspaces/{workspace}')
        ->middleware('needsTenant')
        ->group(function () {});
});
