<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'needsTenant'])->group(function () {
    // domain routes registered here
});
