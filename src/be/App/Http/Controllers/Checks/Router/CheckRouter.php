<?php

use App\Http\Controllers\Checks\CheckController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'check'], function () {
    Route::get('db', [CheckController::class, 'getDB']);
    Route::get('health', [CheckController::class, 'getHealth']);
    Route::get('static', [CheckController::class, 'getStatic']);
    Route::get('ip', [CheckController::class, 'getIP']);
    Route::get('clear_cache', [CheckController::class, 'clearCache']);
});