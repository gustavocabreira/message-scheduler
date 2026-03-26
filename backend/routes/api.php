<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Src\Auth\Http\Controllers\AuthController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::prefix('v1/workspaces/{workspace}')
        ->middleware('needsTenant')
        ->group(function () {});

    Route::get('me', function(Request $request) {
        return $request->user();
    });
});
