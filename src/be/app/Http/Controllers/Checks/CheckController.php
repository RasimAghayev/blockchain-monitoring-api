<?php

namespace App\Http\Controllers\Checks;

use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
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
     * Liveness probe: is the process up and able to serve HTTP at all.
     *
     * Deliberately checks no dependency (DB, Redis, …) — per
     * docs/adr/0003-liveness-readiness-split.md, a dependency outage must not
     * make an orchestrator (k8s, docker compose healthcheck) kill and restart
     * an otherwise-healthy process. That is readiness's job (getReady()
     * below).
     *
     * @OA\Get(
     *      path="/v1/health/live",
     *      operationId="healthLive",
     *      tags={"Health Checks"},
     *      summary="Liveness probe",
     *      description="Process is up and can serve HTTP. No dependency checks.",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Process is alive",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *              @OA\Property(property="path", type="string", example="api/v1/health/live"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(property="error", type="string", nullable=true, example=null),
     *              @OA\Property(property="result", type="object",
     *                  @OA\Property(property="status", type="string", example="alive")
     *              )
     *          )
     *      )
     * )
     */
    public function getLive(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(fn () => ['status' => 'alive']);
    }

    /**
     * Readiness probe: can this process actually serve traffic right now.
     *
     * Checks DB connectivity always, and the Redis connection only when a
     * real dependency on it is configured (`CACHE_STORE=redis` or
     * `QUEUE_CONNECTION=redis`) — checking a connection nothing in the current
     * configuration uses would report false negatives. See
     * docs/adr/0003-liveness-readiness-split.md.
     *
     * @OA\Get(
     *      path="/v1/health/ready",
     *      operationId="healthReady",
     *      tags={"Health Checks"},
     *      summary="Readiness probe",
     *      description="Checks DB (always) and Redis (only if configured as cache/queue driver) connectivity",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Ready to serve traffic",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *              @OA\Property(property="path", type="string", example="api/v1/health/ready"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(property="error", type="string", nullable=true, example=null),
     *              @OA\Property(property="result", type="object",
     *                  @OA\Property(property="status", type="string", example="ready")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(response=503, description="A required dependency is unreachable")
     * )
     */
    public function getReady(): SuccessApiResponse|ErrorApiResponse
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            return ErrorApiResponse::make('Database is not reachable.', 503);
        }

        if (config('cache.default') === 'redis' || config('queue.default') === 'redis') {
            try {
                Redis::connection()->ping();
            } catch (\Throwable $e) {
                return ErrorApiResponse::make('Redis is not reachable.', 503);
            }
        }

        return TransactionHelper::handleWithTransaction(fn () => ['status' => 'ready']);
    }
}
