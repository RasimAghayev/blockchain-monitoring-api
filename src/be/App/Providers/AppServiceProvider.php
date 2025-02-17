<?php

namespace App\Providers;

use App\Http\Controllers\Customers\Models\Customer;
use App\Http\Controllers\Customers\Observers\CustomerObserver;
use App\Http\Controllers\Customers\Repositories\{CustomerRepository, CustomerRepositoryInterface};
use App\Http\Controllers\Customers\Services\{CustomerService, CustomerServiceInterface};
use App\Http\Controllers\FactoryInvoices\Models\FactoryInvoice;
use App\Http\Controllers\FactoryInvoices\Observers\FactoryInvoiceObserver;
use App\Http\Controllers\FactoryInvoices\Repositories\FactoryInvoiceRepository;
use App\Http\Controllers\FactoryInvoices\Repositories\FactoryInvoiceRepositoryInterface;
use App\Http\Controllers\FactoryInvoices\Services\FactoryInvoiceService;
use App\Http\Controllers\FactoryInvoices\Services\FactoryInvoiceServiceInterface;
use App\Http\Controllers\Orders\Models\Order;
use App\Http\Controllers\Orders\Observers\OrderObserver;
use App\Http\Controllers\Orders\Repositories\{OrderRepository, OrderRepositoryInterface};
use App\Http\Controllers\Orders\Services\{OrderService, OrderServiceInterface};
use App\Http\Controllers\Users\Repository\{UserRepository, UserRepositoryInterface};
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
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
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

        Customer::observe(CustomerObserver::class);
    }
}
