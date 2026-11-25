<?php

namespace App\Helpers;

use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class TransactionHelper
{
    public static function handleWithTransaction(callable $callback, int $successStatus = 200): SuccessApiResponse|ErrorApiResponse
    {
        try {
            return SuccessApiResponse::make($callback(), $successStatus);
        } catch (ValidationException $e) {
            return ErrorApiResponse::make($e->errors(), 422);
        } catch (AuthenticationException $e) {
            // Thrown by AuthController::login()/refresh() on bad credentials or
            // an unrefreshable token — distinct from the global `auth:api`
            // middleware's own AuthenticationException (handled in
            // bootstrap/app.php's renderer), which never reaches this catch
            // block because it is thrown before the controller action runs.
            return ErrorApiResponse::make($e->getMessage(), 401);
        } catch (AuthorizationException $e) {
            return ErrorApiResponse::make('Authorization failed: '.$e->getMessage(), 403);
        } catch (ModelNotFoundException|NotFoundHttpException $e) {
            return ErrorApiResponse::make('Resource not found: '.$e->getMessage(), 404);
        } catch (Throwable $e) {
            // The raw exception message is only safe to return when APP_DEBUG is
            // on (docs/REPOSITORY_AUDIT.md P0-3) — this bypasses the global
            // debug-gated renderer in bootstrap/app.php entirely, because the
            // exception is caught here first and never reaches it. Always log
            // the real message; only echo it back to the caller in debug mode.
            Log::error($e->getMessage(), ['exception' => $e]);

            $message = config('app.debug')
                ? 'An error occurred: '.$e->getMessage()
                : 'An error occurred while processing your request.';

            return ErrorApiResponse::make($message, 500);
        }
    }
}
