<?php

use App\Http\Middlewares\CompressResponse;
use App\Http\Middlewares\ContentSecurityPolicyMiddleware;
// ErrorMethodNotAllowedResponse exists but is not wired up yet. Keep it out of the
// group below: a comment inside a group-use list is valid PHP but breaks the Pint
// tokenizer, which reported the whole file as a syntax error.
use App\Http\Middlewares\SecurityHeaders;
use App\Http\Responses\ErrorInternalServerErrorResponse;
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
        // Global Middleware
        $middleware->use([
            TrustProxies::class,
            HandleCors::class,
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
            //            SecurityHeaders::class,
        ]);

        // API Middleware Group
        $middleware->group('api', [
            'throttle:api',
            EnsureFrontendRequestsAreStateful::class,
        ]);

        // API Middleware Group
        $middleware->group('api', [
            ThrottleRequests::class.':60,1',
            EnsureFrontendRequestsAreStateful::class,
            CompressResponse::class,
        ]);
        // API Middleware Group
        $middleware->group('csp', [
            ContentSecurityPolicyMiddleware::class,
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
                    //                    $e instanceof BadMethodCallException => ErrorMethodNotAllowedResponse::make(),
                    $e instanceof ValidationException => ErrorValidationResponse::make($e->errors()),
                    $e instanceof ThrottleRequestsException => ErrorTooManyAttemptsResponse::make(),
                    $e instanceof TokenExpiredException => ErrorTokenExpired::make(),
                    $e instanceof TokenInvalidException => ErrorTokenInvalid::make(),
                    $e instanceof TokenBlacklistedException => ErrorTokenBlacklisted::make(),
                    default => null,
                };
                if (! $response) {
                    $errorMessage = config('App.debug') ? $e->getMessage() : '';
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
