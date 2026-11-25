<?php

use App\Http\Controllers\Checks\CheckController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'check'], function () {
    Route::get('db', [CheckController::class, 'getDB']);
    Route::get('health', [CheckController::class, 'getHealth']);
    Route::get('static', [CheckController::class, 'getStatic']);
    Route::get('ip', [CheckController::class, 'getIP']);
});

// Liveness/readiness split (docs/adr/0003-liveness-readiness-split.md). The
// routes above stay as-is for backward compatibility; these are the ones a
// container orchestrator should actually be pointed at.
Route::group(['prefix' => 'health'], function () {
    Route::get('live', [CheckController::class, 'getLive']);
    Route::get('ready', [CheckController::class, 'getReady']);
});
