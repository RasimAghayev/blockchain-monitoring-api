<?php

namespace App\Providers;

use App\Http\Controllers\Tokens\Models\Token;
use App\Http\Controllers\Tokens\Observers\TokenObserver;
use App\Http\Controllers\Tokens\Repositories\TokenRepository;
use App\Http\Controllers\Tokens\Repositories\TokenRepositoryInterface;
use App\Http\Controllers\Tokens\Services\TokenService;
use App\Http\Controllers\Tokens\Services\TokenServiceInterface;
use App\Http\Controllers\Transactions\Repositories\TransactionRepository;
use App\Http\Controllers\Transactions\Repositories\TransactionRepositoryInterface;
use App\Http\Controllers\Transactions\Services\TransactionService;
use App\Http\Controllers\Transactions\Services\TransactionServiceInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use L5Swagger\Generator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TokenRepositoryInterface::class, TokenRepository::class);
        $this->app->bind(TokenServiceInterface::class, TokenService::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->singleton('L5Swagger\Generator', function ($app) {
            return new Generator($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('local')) {
            $this->app->bind('seed.testing', function () {
                Artisan::call('migrate:fresh --seed');
            });
        }

        Token::observe(TokenObserver::class);
    }
}
