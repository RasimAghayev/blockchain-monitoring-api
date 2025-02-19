<?php

use App\Http\Controllers\Transactions\TransactionController;
use Illuminate\Support\Facades\Route;

Route::group([
//    'middleware' => ['api', 'auth:api'],
    'prefix' => 'transactions'
], function () {
    // List and search
    Route::get('/', [TransactionController::class, 'index'])
        ->name('transactions.index');

    // Create
    Route::post('/', [TransactionController::class, 'store'])
        ->name('transactions.store');

    // Show, update, delete
    Route::get('{id}', [TransactionController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('transactions.show');

    Route::match(['put', 'patch'], '{id}', [TransactionController::class, 'update'])
        ->where('id', '[0-9]+')
        ->name('transactions.update');

    Route::delete('{id}', [TransactionController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('transactions.destroy');

});