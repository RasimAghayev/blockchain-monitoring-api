<?php

namespace App\Http\Controllers\Checks;

use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Http\Responses\{ErrorApiResponse, SuccessApiResponse};
use Illuminate\Support\Facades\{Artisan, DB, Http};

class CheckController extends Controller
{
    /**
     * @return : SuccessApiResponse|ErrorApiResponse
     */
    public function getDB(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () {
            DB::connection()->getPdo();
            return ['status' => 'Application is up and running, database connection is ok!'];
        });
    }

    /**
     * @return SuccessApiResponse|ErrorApiResponse
     */
    public function getHealth(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () {
            return ['status' => 'Application is up and running, health is ok!'];
        });
    }

    /**
     * @return SuccessApiResponse|ErrorApiResponse
     */
    public function getStatic(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () {
            return ['status' => true];
        });
    }

    /**
     * @return mixed
     */
    public function getIP(): mixed
    {
        return TransactionHelper::handleWithTransaction(function () {
            return Http::get('https://ipapi.co/json/')->json();
        });
    }

    /**
     * @return SuccessApiResponse|ErrorApiResponse
     */
    public function clearCache(): SuccessApiResponse|ErrorApiResponse
    {
        Artisan::call('optimize:clear');
        Artisan::call('config:cache');
        Artisan::call('config:clear');
        Artisan::call('clear-compiled');
        Artisan::call('cache:clear');
        Artisan::call('route:cache');
        Artisan::call('view:clear');

        return TransactionHelper::handleWithTransaction(function () {
            return ['status' => "Cache is cleared " . date(now())];
        });
    }
}