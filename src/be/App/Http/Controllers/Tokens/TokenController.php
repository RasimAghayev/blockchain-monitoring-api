<?php
declare(strict_types=1);

namespace App\Http\Controllers\Tokens;

use App\Helpers\CachedApiCallHelper;
use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Tokens\{Models\Token,
    Models\TokenHolder,
    Requests\StoreTokenRequest,
    Requests\UpdateTokenRequest,
    Resources\TokenCollection,
    Resources\TokenResource,
    Services\TokenServiceInterface};
use App\Http\Responses\{ErrorApiResponse, ErrorValidationResponse, SuccessApiResponse};
use App\Services\Blockchain\BlockchainService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *      name="Tokens",
 *      description="API Endpoints of Tokens Management System"
 * )
 * @method static make(string[] $array, int $int)
 */
class TokenController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TokenServiceInterface $tokenService,
        private readonly BlockchainService     $blockchainService,
    )
    {
    }

    /**
     * Get list of tokens
     *
     * @param Request $request
     * @return TokenCollection|ErrorApiResponse|SuccessApiResponse
     */

    /**
     * @OA\Get(
     *      path="/v1/tokens",
     *      operationId="getTokensList",
     *      tags={"Tokens"},
     *      summary="Get list of tokens",
     *      description="Returns list of tokens",
     *      @OA\Parameter(
     *          name="include_tags",
     *          in="query",
     *          description="Include tags in the response",
     *          required=false,
     *          @OA\Schema(
     *              type="boolean"
     *          )
     *      ),
     *        @OA\Parameter(
     *            name="tokenAddress[lk]",
     *            in="query",
     *            description="Search query [eq,lt,lte,gt,gte,ne,lk,ilk,nlk,inlk,bt,nbt,in,nin,json]",
     *            required=false,
     *            @OA\Schema(
     *                type="string"
     *            )
     *        ),
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Number of items per page",
     *          required=false,
     *          @OA\Schema(
     *              type="integer",
     *              default=15
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *               type="object",
     *               @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *               @OA\Property(property="path", type="string", example="api/v1/tokens"),
     *               @OA\Property(property="method", type="string", example="GET"),
     *               @OA\Property(property="error", type="string", nullable=true, example=null),
     *               @OA\Property(property="result", type="object", ref="#/components/schemas/TokenCollection")
     *           )
     *      ),
     *      @OA\Response(response=401,description="Unauthenticated",),
     *      @OA\Response(response=403,description="Forbidden")
     * )
     */
    public function index(Request $request): TokenCollection|SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () use ($request) {
//            $this->authorize('viewAny', Token::class);
            $tokens = $this->tokenService->getTokens(
                request: $request,
                includeTags: $request->boolean('include_tags'),
                perPage: $request->integer('per_page', 15)
            );
            return new TokenCollection($tokens);
        });
    }

    /**
     * Create new token
     *
     * @param StoreTokenRequest $request
     * @return ErrorApiResponse|ErrorValidationResponse|SuccessApiResponse
     */

    /**
     * @OA\Post(
     *      path="/v1/tokens",
     *      operationId="storeToken",
     *      tags={"Tokens"},
     *      summary="Store new token",
     *      description="Returns token data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/StoreTokenRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *               type="object",
     *               @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *               @OA\Property(property="path", type="string", example="api/v1/tokens"),
     *               @OA\Property(property="method", type="string", example="POST"),
     *               @OA\Property(property="error", type="string", nullable=true, example=null),
     *               @OA\Property(
     *                       property="result",
     *                       type="object",
     *                       @OA\Property(property="message", type="string", example="Token successfully created"),
     *                       @OA\Property(property="data", type="object", ref="#/components/schemas/TokenResource")
     *                  )
     *           )
     *       ),
     *      @OA\Response(response=401,description="Unauthenticated",),
     *      @OA\Response(response=403,description="Forbidden"),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *           @OA\JsonContent(
     *               type="object",
     *               @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *               @OA\Property(property="path", type="string", example="api/v1/tokens"),
     *               @OA\Property(property="method", type="string", example="POST"),
     *               @OA\Property(
     *                   property="error",
     *                   type="object",
     *                   @OA\AdditionalProperties(
     *                       type="array",
     *                       @OA\Items(type="string", example="Address information is not recorded.")
     *                   )
     *               ),
     *               @OA\Property(property="result",type="object",example={})
     *           )
     *      )
     * )
     */
    public function store(StoreTokenRequest $request): SuccessApiResponse|ErrorApiResponse|ErrorValidationResponse
    {
        return TransactionHelper::handleWithTransaction(function () use ($request) {
//            $this->authorize('createAny', Token::class);
            $token = $this->tokenService->createToken($request->toDTO());

            return [
                'message' => 'Token successfully created',
                'data' => new TokenResource($token)
            ];
        }, 201);
    }

    /**
     * Get token by ID
     *
     * @param int $id
     * @return SuccessApiResponse|ErrorApiResponse
     */
    /**
     * @OA\Get(
     *      path="/v1/tokens/{id}",
     *      operationId="getTokenById",
     *      tags={"Tokens"},
     *      summary="Get token information",
     *      description="Returns token data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Token id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *              @OA\Property(property="path", type="string", example="api/v1/tokens/1"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(property="error", type="string", nullable=true, example=null),
     *              @OA\Property(
     *                      property="result",
     *                      type="object",
     *                      @OA\Property(property="message", type="string", example="Token successfully show: 10"),
     *                      @OA\Property(property="data", type="object", ref="#/components/schemas/TokenResource")
     *                 )
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *              @OA\Property(property="path", type="string", example="api/v1/tokens/1"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(
     *                  property="error",
     *                  type="object",
     *                  example="Resource not found: Token not found with ID: 10"
     *              ),
     *              @OA\Property(property="result",type="object",example={})
     *            )
     *          )
     *      )
     * )
     */
    public function show(int $id): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () use ($id) {
            $token = $this->tokenService->getTokenById($id);
            if (is_string($token)) {
                throw new ModelNotFoundException($token);
            }
//            $this->authorize('view', Token::class);
            return [
                'message' => 'Token successfully show: ' . $id,
                'data' => new TokenResource($token)
            ];
        });
    }

    /**
     * Update existing token
     *
     * @param UpdateTokenRequest $request
     * @param int $id
     * @return SuccessApiResponse|ErrorApiResponse
     */

    /**
     * @OA\Patch(
     *      path="/v1/tokens/{id}",
     *      operationId="updateTokenPatch",
     *      tags={"Tokens"},
     *      summary="Update existing token",
     *      description="Returns updated token data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Token id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateTokenRequest")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *             @OA\JsonContent(
     *                 type="object",
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *                 @OA\Property(property="path", type="string", example="api/v1/tokens"),
     *                 @OA\Property(property="method", type="string", example="PATCH"),
     *                 @OA\Property(property="error", type="string", nullable=true, example=null),
     *                 @OA\Property(
     *                         property="result",
     *                         type="object",
     *                         @OA\Property(property="message", type="string", example="Token successfully created"),
     *                         @OA\Property(property="data", type="object", ref="#/components/schemas/TokenResource")
     *                    )
     *             )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *       @OA\Response(
     *           response=404,
     *           description="Resource Not Found",
     *           @OA\JsonContent(
     *               type="object",
     *               @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *               @OA\Property(property="path", type="string", example="api/v1/tokens/{id}"),
     *               @OA\Property(property="method", type="string", example="PATCH"),
     *               @OA\Property(
     *                   property="error",
     *                   type="object",
     *                   example="Resource not found: Token not found with ID: 10"
     *               ),
     *               @OA\Property(property="result",type="object",example={})
     *           )
     *       ),
     *        @OA\Response(
     *            response=422,
     *            description="Unprocessable Entity"
     *        )
     * )
     */
    public function update(UpdateTokenRequest $request, int $id): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () use ($request, $id) {
            $token = $this->tokenService->getTokenById($id);
            if (is_string($token)) {
                throw new ModelNotFoundException($token);
            }
//            $this->authorize('update', $token);
            $updatedToken = $this->tokenService->updateToken($id, $request->toDTO());

            return [
                'message' => 'Token successfully updated',
                'data' => new TokenResource($updatedToken)
            ];
        });
    }

    /**
     * @OA\Put(
     *      path="/v1/tokens/{id}",
     *      operationId="updateTokenPut",
     *      tags={"Tokens"},
     *      summary="Update existing token",
     *      description="Returns updated token data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Token id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateTokenRequest")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *            @OA\JsonContent(
     *                type="object",
     *                @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *                @OA\Property(property="path", type="string", example="api/v1/tokens"),
     *                @OA\Property(property="method", type="string", example="PUT"),
     *                @OA\Property(property="error", type="string", nullable=true, example=null),
     *                @OA\Property(
     *                        property="result",
     *                        type="object",
     *                        @OA\Property(property="message", type="string", example="Token successfully created"),
     *                        @OA\Property(property="data", type="object", ref="#/components/schemas/TokenResource")
     *                   )
     *            )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *       @OA\Response(
     *           response=404,
     *           description="Resource Not Found",
     *           @OA\JsonContent(
     *               type="object",
     *               @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *               @OA\Property(property="path", type="string", example="api/v1/tokens/1"),
     *               @OA\Property(property="method", type="string", example="PUT"),
     *               @OA\Property(
     *                   property="error",
     *                   type="object",
     *                   example="Resource not found: Token not found with ID: 10"
     *               ),
     *               @OA\Property(property="result",type="object",example={})
     *             )
     *       ),
     *       @OA\Response(
     *           response=422,
     *           description="Unprocessable Entity"
     *       )
     * )
     */
    private function updateA()
    {
    }
    /**
     * Delete token
     *
     * @param int $id
     * @return SuccessApiResponse|ErrorApiResponse
     */
    /**
     * @OA\Delete(
     *      path="/v1/tokens/{id}",
     *      operationId="deleteToken",
     *      tags={"Tokens"},
     *      summary="Delete existing token",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Token id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *       @OA\Response(
     *           response=404,
     *           description="Resource Not Found",
     *           @OA\JsonContent(
     *               type="object",
     *               @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *               @OA\Property(property="path", type="string", example="api/v1/tokens/{id}"),
     *               @OA\Property(property="method", type="string", example="DELETE"),
     *               @OA\Property(
     *                   property="error",
     *                   type="object",
     *                   example="Resource not found: Token not found with ID: 10"
     *               ),
     *               @OA\Property(property="result",type="object",example={})
     *             )
     *           )
     *       )
     * )
     */
    public function destroy(int $id): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () use ($id) {
            $token = $this->tokenService->getTokenById($id);
            if (is_string($token)) {
                throw new ModelNotFoundException($token);
            }
//            $this->authorize('delete', $token);
            $this->tokenService->deleteToken($id);

            return [
                'message' => 'Token successfully deleted'
            ];
        }, 204);
    }

    /**
     * Get token information
     *
     * @param string $address
     * @return JsonResponse
     *
     * @OA\Get(
     *      path="/v1/tokens/{address}/info",
     *      operationId="getTokenInfo",
     *      tags={"Tokens"},
     *      summary="Get token information",
     *      description="Returns token information for a given address",
     *      @OA\Parameter(
     *          name="address",
     *          description="Token address",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="name", type="string", example="Ethereum"),
     *              @OA\Property(property="symbol", type="string", example="ETH"),
     *              @OA\Property(property="total_supply", type="string", example="115714953")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Token not found"
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server error"
     *      )
     * )
     */
    public function getTokenInfo(string $address): JsonResponse
    {
        return CachedApiCallHelper::cachedApiCall("token_info_{$address}", 10, function () use ($address) {
            $tokenInfo = $this->blockchainService->getTokenInfo($address);

            if ($tokenInfo->status() === 200) {
                $data = $tokenInfo->getData(true);
                $this->storeOrUpdateToken($address, $data);
            }

            return $tokenInfo;
        });
    }
    private function storeOrUpdateToken(string $address, array $tokenData): void
    {
        Token::updateOrCreate(
            ['address' => $address],
            [
                'name' => $tokenData['name'] ?? 'Unknown',
                'symbol' => $tokenData['symbol'] ?? 'N/A',
                'total_supply' => $tokenData['total_supply'] ?? 0,
            ]
        );
    }
    /**
     * Get top token holders
     *
     * @param string $address
     * @return JsonResponse
     *
     * @OA\Get(
     *      path="/v1/tokens/{address}/top-holders",
     *      operationId="getTopHolders",
     *      tags={"Tokens"},
     *      summary="Get top token holders",
     *      description="Returns top holders for a given token address",
     *      @OA\Parameter(
     *          name="address",
     *          description="Token address",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="token_address", type="string", example="0x123456789abcdef..."),
     *              @OA\Property(
     *                  property="top_holders",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="address", type="string", example="0xabcdef123456..."),
     *                      @OA\Property(property="balance", type="string", example="1000000000000000000"),
     *                      @OA\Property(property="percentage", type="string", example="10.5")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Token not found"
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server error"
     *      )
     * )
     */
    public function getTopHolders(string $address): JsonResponse
    {
        return CachedApiCallHelper::cachedApiCall("top_holders_{$address}", 10, function () use ($address) {
            $topHolders = $this->blockchainService->getTopHolders($address);

            if ($topHolders->status() === 200) {
                $data = $topHolders->getData(true);
                $this->storeOrUpdateTokenHolders($address, $data['top_holders'] ?? []);
            }

            return $topHolders;
        });
    }

    private function storeOrUpdateTokenHolders(string $tokenAddress, array $holders): void
    {
        TokenHolder::where('token_address', $tokenAddress)->delete();

        $holdersData = array_map(function ($holder) use ($tokenAddress) {
            return [
                'token_address' => $tokenAddress,
                'holder_address' => $holder['address'],
                'balance' => $holder['balance'],
                'percentage' => $holder['percentage']
            ];
        }, $holders);

        TokenHolder::insert($holdersData);
    }

}