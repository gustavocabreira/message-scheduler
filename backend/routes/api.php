<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'needsTenant'])->group(function () {
    // domain routes registered here
});
