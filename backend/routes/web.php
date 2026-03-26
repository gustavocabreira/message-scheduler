<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Auth\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('huggy', [AuthController::class, 'redirect'])->name('huggy.redirect');
    Route::get('huggy/callback', [AuthController::class, 'callback'])->name('huggy.callback');
});
