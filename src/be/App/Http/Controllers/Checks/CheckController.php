<?php

namespace App\Http\Controllers\Checks;

use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Health Checks",
 *     description="System health and database connectivity checks"
 * )
 */
class CheckController extends Controller
{
    /**
     * Check database connectivity.
     *
     *
     * @OA\Get(
     *      path="/v1/check/db",
     *      operationId="checkDatabase",
     *      tags={"Health Checks"},
     *      summary="Check database connectivity",
     *      description="Verifies the application can connect to the database",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Database connection successful",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *              @OA\Property(property="path", type="string", example="api/v1/check/db"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(property="error", type="string", nullable=true, example=null),
     *              @OA\Property(property="result", type="object",
     *                  @OA\Property(property="status", type="string", example="Application is up and running, database connection is ok!")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(response=500, description="Database connection failed")
     * )
     */
    public function getDB(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () {
            DB::connection()->getPdo();

            return ['status' => 'Application is up and running, database connection is ok!'];
        });
    }

    /**
     * Check application health.
     *
     *
     * @OA\Get(
     *      path="/v1/check/health",
     *      operationId="checkHealth",
     *      tags={"Health Checks"},
     *      summary="Check application health",
     *      description="Returns application health status",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Application is healthy",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *              @OA\Property(property="path", type="string", example="api/v1/check/health"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(property="error", type="string", nullable=true, example=null),
     *              @OA\Property(property="result", type="object",
     *                  @OA\Property(property="status", type="string", example="Application is up and running, health is ok!")
     *              )
     *          )
     *      )
     * )
     */
    public function getHealth(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () {
            return ['status' => 'Application is up and running, health is ok!'];
        });
    }

    /**
     * Check static content.
     *
     *
     * @OA\Get(
     *      path="/v1/check/static",
     *      operationId="checkStatic",
     *      tags={"Health Checks"},
     *      summary="Check static content",
     *      description="Returns a static health status",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Static check successful",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *              @OA\Property(property="path", type="string", example="api/v1/check/static"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(property="error", type="string", nullable=true, example=null),
     *              @OA\Property(property="result", type="object",
     *                  @OA\Property(property="status", type="boolean", example=true)
     *              )
     *          )
     *      )
     * )
     */
    public function getStatic(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () {
            return ['status' => true];
        });
    }

    /**
     * Get the server's public IP address.
     *
     * @return JsonResponse
     *
     * @OA\Get(
     *      path="/v1/check/ip",
     *      operationId="checkIp",
     *      tags={"Health Checks"},
     *      summary="Get server IP address",
     *      description="Returns the server's public IP address and location info",
     *
     *      @OA\Response(
     *          response=200,
     *          description="IP address retrieved successfully",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *              @OA\Property(property="path", type="string", example="api/v1/check/ip"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(property="error", type="string", nullable=true, example=null),
     *              @OA\Property(property="result", type="object",
     *                  @OA\Property(property="ip", type="string", example="192.168.1.1"),
     *                  @OA\Property(property="city", type="string", example="Baku"),
     *                  @OA\Property(property="region", type="string", example="Baku"),
     *                  @OA\Property(property="country", type="string", example="AZ"),
     *                  @OA\Property(property="loc", type="string", example="40.3745,49.8556")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(response=502, description="Failed to retrieve IP information")
     * )
     */
    public function getIP(): mixed
    {
        return TransactionHelper::handleWithTransaction(function () {
            return Http::get('https://ipapi.co/json/')->json();
        });
    }

    /**
     * Clear all application caches.
     *
     *
     * @OA\Get(
     *      path="/v1/check/clear_cache",
     *      operationId="clearCache",
     *      tags={"Health Checks"},
     *      summary="Clear all application caches",
     *      description="Clears config, route, view, and application caches",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Cache cleared successfully",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *              @OA\Property(property="path", type="string", example="api/v1/check/clear_cache"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(property="error", type="string", nullable=true, example=null),
     *              @OA\Property(property="result", type="object",
     *                  @OA\Property(property="status", type="string", example="Cache is cleared 2025-02-15 23:21:32")
     *              )
     *          )
     *      )
     * )
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
            return ['status' => 'Cache is cleared '.date(now())];
        });
    }
}
