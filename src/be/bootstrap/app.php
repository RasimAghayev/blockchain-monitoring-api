<?php

use App\Http\Middlewares\CompressResponse;
use App\Http\Middlewares\ContentSecurityPolicyMiddleware;
use App\Http\Middlewares\SecurityHeaders;
use App\Http\Responses\ErrorInternalServerErrorResponse;
use App\Http\Responses\ErrorMethodNotAllowedResponse;
use App\Http\Responses\ErrorNotFoundResponse;
use App\Http\Responses\ErrorTokenBlacklisted;
use App\Http\Responses\ErrorTokenExpired;
use App\Http\Responses\ErrorTokenInvalid;
use App\Http\Responses\ErrorTooManyAttemptsResponse;
use App\Http\Responses\ErrorUnauthenticatedResponse;
use App\Http\Responses\ErrorValidationResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use phpOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use phpOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use phpOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware) {
        // This app has no web login page (routes/web.php only serves the
        // default welcome view) — it is a pure JSON API. The framework's
        // own default guest-redirect, registered unconditionally in
        // Illuminate\Foundation\Configuration\ApplicationBuilder::withMiddleware()
        // as `redirectGuestsTo(fn () => route('login'))`, assumes a named
        // 'login' route exists. Without this override, every unauthenticated
        // request to an `auth:api`-gated route that doesn't send an
        // `Accept: application/json` header (so `expectsJson()` is false —
        // true for a plain `curl` call, false for a browser `fetch()`/axios
        // client that does send it) hits that default: `route('login')`
        // throws Symfony\Component\Routing\Exception\RouteNotFoundException,
        // which is caught by the renderable() handler below and mapped to a
        // 404 — silently masking what should be a 401. This was only ever
        // exercised once `auth:api` gating was actually turned on this phase
        // (docs/adr/0001-flat-authorization.md); PHPUnit's test HTTP client
        // always sends `Accept: application/json`, which is why the bug was
        // invisible to the test suite and only surfaced against the real
        // nginx+php-fpm stack. Always returning null means
        // AuthenticationException's redirect path is always null, so it
        // reaches ErrorUnauthenticatedResponse below as intended.
        $middleware->redirectGuestsTo(fn () => null);

        // Global Middleware. SecurityHeaders and ContentSecurityPolicyMiddleware
        // both only ever set response headers, so they belong here rather than in
        // a named group that has to be remembered on every route list: this way
        // they also cover non-'api'-prefixed responses (e.g. the L5Swagger docs
        // UI, which registers its own routes outside our 'api' group) without
        // needing to be attached twice. ContentSecurityPolicyMiddleware is
        // already path-aware internally (looser CSP under api/documentation*).
        $middleware->use([
            TrustProxies::class,
            HandleCors::class,
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
            SecurityHeaders::class,
            ContentSecurityPolicyMiddleware::class,
        ]);

        // API Middleware Group. The group is defined once: a second definition
        // replaced the first one, and the first one carried a "throttle:api"
        // named limiter that was never registered. Any request that reached it
        // failed with "Rate limiter [api] is not defined.".
        $middleware->group('api', [
            ThrottleRequests::class.':60,1',
            EnsureFrontendRequestsAreStateful::class,
            CompressResponse::class,
        ]);

        // Route Specific Middleware Aliases
        $middleware->alias([
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'auth.throttle' => ThrottleRequests::class.':30,1',
        ]);
    })
    ->withExceptions(using: function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                if ($e instanceof HttpResponseException) {
                    return null;
                }
                $response = match (true) {
                    $e instanceof RouteNotFoundException => ErrorNotFoundResponse::make(),
                    $e instanceof NotFoundHttpException => ErrorNotFoundResponse::make(),
                    $e instanceof AuthenticationException => ErrorUnauthenticatedResponse::make(),
                    // Laravel's router throws this (not BadMethodCallException, which is
                    // a plain PHP "no such method" error, not an HTTP concern) when a
                    // route path matches but the HTTP verb doesn't — e.g. DELETE on a
                    // path that only defines GET/POST. Previously unmapped, so a real
                    // 405 fell through to the generic 500 branch below with a
                    // misleadingly generic message.
                    $e instanceof MethodNotAllowedHttpException => ErrorMethodNotAllowedResponse::make(),
                    $e instanceof ValidationException => ErrorValidationResponse::make($e->errors()),
                    $e instanceof ThrottleRequestsException => ErrorTooManyAttemptsResponse::make(),
                    $e instanceof TokenExpiredException => ErrorTokenExpired::make(),
                    $e instanceof TokenInvalidException => ErrorTokenInvalid::make(),
                    $e instanceof TokenBlacklistedException => ErrorTokenBlacklisted::make(),
                    default => null,
                };
                if (! $response) {
                    $errorMessage = config('app.debug') ? $e->getMessage() : '';
                    $response = ErrorInternalServerErrorResponse::make($errorMessage);
                }
                if ($response instanceof Response && $e instanceof HttpException) {
                    $headers = $e->getHeaders();
                    $response->headers->add($headers);
                }

                return $response;
            }

            return null;
        });

        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
