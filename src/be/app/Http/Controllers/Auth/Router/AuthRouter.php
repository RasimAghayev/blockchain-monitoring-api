<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Issuing a token cannot itself require one.
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware(['auth:api'])->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
});
