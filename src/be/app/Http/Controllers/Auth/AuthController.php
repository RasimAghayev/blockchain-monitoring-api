<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Helpers\TransactionHelper;
use App\Http\Controllers\Auth\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Issues and manages the JWT used by the `api` guard (`auth:api`).
 *
 * This controller exists because re-enabling `auth:api` on the Tokens/
 * Transactions routers (docs/REPOSITORY_AUDIT.md P0-1) was previously
 * impossible to do safely: every piece of JWT infrastructure (config, the
 * `User` model, the migration) was present, but nothing in the repo ever
 * issued a token. Without a login endpoint, enabling the guard would have
 * locked every caller out with no way back in.
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="JWT issuance for the `api` guard"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/v1/auth/login",
     *      operationId="login",
     *      tags={"Auth"},
     *      summary="Exchange email/password for a JWT access token",
     *
     *      @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/LoginRequest")),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Authenticated",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(property="result", type="object",
     *                  @OA\Property(property="access_token", type="string"),
     *                  @OA\Property(property="token_type", type="string", example="bearer"),
     *                  @OA\Property(property="expires_in", type="integer")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(LoginRequest $request): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () use ($request) {
            $credentials = $request->validated();

            if (! $token = Auth::guard('api')->attempt($credentials)) {
                throw new AuthenticationException('Invalid credentials.');
            }

            return $this->tokenResponse($token);
        });
    }

    /**
     * @OA\Get(
     *      path="/v1/auth/me",
     *      operationId="me",
     *      tags={"Auth"},
     *      summary="Get the authenticated user",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Response(response=200, description="The authenticated user"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(fn () => Auth::guard('api')->user());
    }

    /**
     * @OA\Post(
     *      path="/v1/auth/refresh",
     *      operationId="refreshToken",
     *      tags={"Auth"},
     *      summary="Exchange a still-valid token for a new one",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Response(response=200, description="A new access token")
     * )
     */
    public function refresh(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(
            fn () => $this->tokenResponse(Auth::guard('api')->refresh())
        );
    }

    /**
     * @OA\Post(
     *      path="/v1/auth/logout",
     *      operationId="logout",
     *      tags={"Auth"},
     *      summary="Invalidate the current token",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Response(response=200, description="Logged out")
     * )
     */
    public function logout(): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () {
            Auth::guard('api')->logout();

            return ['message' => 'Successfully logged out.'];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenResponse(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ];
    }
}
