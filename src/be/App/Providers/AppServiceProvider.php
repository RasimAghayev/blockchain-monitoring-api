<?php

namespace App\Providers;

use App\Http\Controllers\Tokens\Models\Token;
use App\Http\Controllers\Tokens\Observers\TokenObserver;
use App\Http\Controllers\Tokens\Repositories\{TokenRepository, TokenRepositoryInterface};
use App\Http\Controllers\Tokens\Services\{TokenService, TokenServiceInterface};
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
