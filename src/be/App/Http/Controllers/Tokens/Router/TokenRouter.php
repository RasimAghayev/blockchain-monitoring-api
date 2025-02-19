<?php

use App\Http\Controllers\Tokens\TokenController;
use Illuminate\Support\Facades\Route;

Route::group([
//    'middleware' => ['api', 'auth:api'],
    'prefix' => 'tokens'
], function () {
    // List and search
    Route::get('/', [TokenController::class, 'index'])
        ->name('tokens.index');

    // Create
    Route::post('/', [TokenController::class, 'store'])
        ->name('tokens.store');

    // Show, update, delete
    Route::get('{id}', [TokenController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('tokens.show');

    Route::match(['put', 'patch'], '{id}', [TokenController::class, 'update'])
        ->where('id', '[0-9]+')
        ->name('tokens.update');

    Route::delete('{id}', [TokenController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('tokens.destroy');

    Route::get('{address}/info', [TokenController::class, 'getTokenInfo'])
        ->where('address', '[A-Za-z0-9]+')
        ->name('tokens.info');

    Route::get('{address}/top-holders', [TokenController::class, 'getTopHolders'])
        ->where('address', '[A-Za-z0-9]+')
        ->name('tokens.top.holders');
});