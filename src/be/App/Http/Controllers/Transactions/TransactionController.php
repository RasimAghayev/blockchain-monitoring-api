<?php
declare(strict_types=1);

namespace App\Http\Controllers\Transactions;

use App\Helpers\CachedApiCallHelper;
use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Transactions\{Models\Transaction,
    Requests\StoreTransactionRequest,
    Requests\UpdateTransactionRequest,
    Resources\TransactionCollection,
    Resources\TransactionResource,
    Services\TransactionServiceInterface};
use App\Http\Responses\{ErrorApiResponse, ErrorValidationResponse, SuccessApiResponse};
use App\Services\Blockchain\BlockchainService;
use App\Services\Telegram\TelegramService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *      name="Transactions",
 *      description="API Endpoints of Transactions Management System"
 * )
 * @method static make(string[] $array, int $int)
 */
class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TransactionServiceInterface $transactionService,
        private readonly TelegramService             $telegramService,
        private readonly BlockchainService           $blockchainService,
    )
    {
    }

    /**
     * Get list of transactions
     *
     * @param Request $request
     * @return TransactionCollection|ErrorApiResponse|SuccessApiResponse
     */

    /**
     * @OA\Get(
     *      path="/v1/transactions",
     *      operationId="getTransactionsList",
     *      tags={"Transactions"},
     *      summary="Get list of transactions",
     *      description="Returns list of transactions",
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
     *            name="name[lk]",
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
     *               @OA\Property(property="path", type="string", example="api/v1/transactions"),
     *               @OA\Property(property="method", type="string", example="GET"),
     *               @OA\Property(property="error", type="string", nullable=true, example=null),
     *               @OA\Property(property="result", type="object", ref="#/components/schemas/TransactionCollection")
     *           )
     *      ),
     *      @OA\Response(response=401,description="Unauthenticated",),
     *      @OA\Response(response=403,description="Forbidden")
     * )
     */
    public function index(Request $request): TransactionCollection|SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () use ($request) {
//            $this->authorize('viewAny', Transaction::class);
            $transactions = $this->transactionService->getTransactions(
                request: $request,
                includeTags: $request->boolean('include_tags'),
                perPage: $request->integer('per_page', 15)
            );
            return new TransactionCollection($transactions);
        });
    }

    /**
     * Create new transaction
     *
     * @param StoreTransactionRequest $request
     * @return ErrorApiResponse|ErrorValidationResponse|SuccessApiResponse
     */

    /**
     * @OA\Post(
     *      path="/v1/transactions",
     *      operationId="storeTransaction",
     *      tags={"Transactions"},
     *      summary="Store new transaction",
     *      description="Returns transaction data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/StoreTransactionRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *           @OA\JsonContent(
     *               type="object",
     *               @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *               @OA\Property(property="path", type="string", example="api/v1/transactions"),
     *               @OA\Property(property="method", type="string", example="POST"),
     *               @OA\Property(property="error", type="string", nullable=true, example=null),
     *               @OA\Property(
     *                       property="result",
     *                       type="object",
     *                       @OA\Property(property="message", type="string", example="Transaction successfully created"),
     *                       @OA\Property(property="data", type="object", ref="#/components/schemas/TransactionResource")
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
     *               @OA\Property(property="path", type="string", example="api/v1/transactions"),
     *               @OA\Property(property="method", type="string", example="POST"),
     *               @OA\Property(
     *                   property="error",
     *                   type="object",
     *                   @OA\AdditionalProperties(
     *                       type="array",
     *                       @OA\Items(type="string", example="Şirkət məlumatları qeyd edilməyib.")
     *                   )
     *               ),
     *               @OA\Property(property="result",type="object",example={})
     *           )
     *      )
     * )
     */
    public function store(StoreTransactionRequest $request): SuccessApiResponse|ErrorApiResponse|ErrorValidationResponse
    {
        return TransactionHelper::handleWithTransaction(function () use ($request) {
//            $this->authorize('createAny', Transaction::class);
            $transaction = $this->transactionService->createTransaction($request->toDTO());

            return [
                'message' => 'Transaction successfully created',
                'data' => new TransactionResource($transaction)
            ];
        }, 201);
    }

    /**
     * Get transaction by ID
     *
     * @param int $id
     * @return SuccessApiResponse|ErrorApiResponse
     */
    /**
     * @OA\Get(
     *      path="/v1/transactions/{id}",
     *      operationId="getTransactionById",
     *      tags={"Transactions"},
     *      summary="Get transaction information",
     *      description="Returns transaction data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Transaction id",
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
     *              @OA\Property(property="path", type="string", example="api/v1/transactions/1"),
     *              @OA\Property(property="method", type="string", example="POST"),
     *              @OA\Property(property="error", type="string", nullable=true, example=null),
     *              @OA\Property(
     *                      property="result",
     *                      type="object",
     *                      @OA\Property(property="message", type="string", example="Transaction successfully show: 10"),
     *                      @OA\Property(property="data", type="object", ref="#/components/schemas/TransactionResource")
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
     *              @OA\Property(property="path", type="string", example="api/v1/transactions/1"),
     *              @OA\Property(property="method", type="string", example="GET"),
     *              @OA\Property(
     *                  property="error",
     *                  type="object",
     *                  example="Resource not found: Transaction not found with ID: 10"
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
            $transaction = $this->transactionService->getTransactionById($id);
            if (is_string($transaction)) {
                throw new ModelNotFoundException($transaction);
            }
//            $this->authorize('view', Transaction::class);
            return [
                'message' => 'Transaction successfully show: ' . $id,
                'data' => new TransactionResource($transaction)
            ];
        });
    }

    /**
     * Update existing transaction
     *
     * @param UpdateTransactionRequest $request
     * @param int $id
     * @return SuccessApiResponse|ErrorApiResponse
     */

    /**
     * @OA\Patch(
     *      path="/v1/transactions/{id}",
     *      operationId="updateTransactionPatch",
     *      tags={"Transactions"},
     *      summary="Update existing transaction",
     *      description="Returns updated transaction data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Transaction id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateTransactionRequest")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/TransactionResource")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
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
     *               @OA\Property(property="path", type="string", example="api/v1/transactions/{id}"),
     *               @OA\Property(property="method", type="string", example="GET"),
     *               @OA\Property(
     *                   property="error",
     *                   type="object",
     *                   example="Resource not found: Transaction not found with ID: 10"
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
    public function update(UpdateTransactionRequest $request, int $id): SuccessApiResponse|ErrorApiResponse
    {
        return TransactionHelper::handleWithTransaction(function () use ($request, $id) {
            $transaction = $this->transactionService->getTransactionById($id);
            if (is_string($transaction)) {
                throw new ModelNotFoundException($transaction);
            }
//            $this->authorize('update', $transaction);
            $updatedTransaction = $this->transactionService->updateTransaction($id, $request->toDTO());

            return [
                'message' => 'Transaction successfully updated',
                'data' => new TransactionResource($updatedTransaction)
            ];
        });
    }

    /**
     * @OA\Delete(
     *      path="/v1/transactions/{id}",
     *      operationId="deleteTransaction",
     *      tags={"Transactions"},
     *      summary="Delete existing transaction",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Transaction id",
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
     *               @OA\Property(property="path", type="string", example="api/v1/transactions/{id}"),
     *               @OA\Property(property="method", type="string", example="GET"),
     *               @OA\Property(
     *                   property="error",
     *                   type="object",
     *                   example="Resource not found: Transaction not found with ID: 10"
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
            $transaction = $this->transactionService->getTransactionById($id);
            if (is_string($transaction)) {
                throw new ModelNotFoundException($transaction);
            }
//            $this->authorize('delete', $transaction);
            $this->transactionService->deleteTransaction($id);

            return [
                'message' => 'Transaction successfully deleted'
            ];
        }, 204);
    }

    /**
     * Delete transaction
     *
     * @param int $id
     * @return SuccessApiResponse|ErrorApiResponse
     */

    /**
     * @OA\Put(
     *      path="/v1/transactions/{id}",
     *      operationId="updateTransactionPut",
     *      tags={"Transactions"},
     *      summary="Update existing transaction",
     *      description="Returns updated transaction data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Transaction id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateTransactionRequest")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *            @OA\JsonContent(
     *                type="object",
     *                @OA\Property(property="timestamp", type="string", format="date-time", example="2025-02-15T23:21:32+04:00"),
     *                @OA\Property(property="path", type="string", example="api/v1/transactions"),
     *                @OA\Property(property="method", type="string", example="POST"),
     *                @OA\Property(property="error", type="string", nullable=true, example=null),
     *                @OA\Property(
     *                        property="result",
     *                        type="object",
     *                        @OA\Property(property="message", type="string", example="Transaction successfully created"),
     *                        @OA\Property(property="data", type="object", ref="#/components/schemas/TransactionResource")
     *                   )
     *            )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
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
     *               @OA\Property(property="path", type="string", example="api/v1/transactions/1"),
     *               @OA\Property(property="method", type="string", example="GET"),
     *               @OA\Property(
     *                   property="error",
     *                   type="object",
     *                   example="Resource not found: Transaction not found with ID: 10"
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
     * Get latest transactions for a token
     *
     * @param string $address
     * @return JsonResponse
     *
     * @OA\Get(
     *      path="/v1/tokens/{address}/latest-transactions",
     *      operationId="getLatestTransactions",
     *      tags={"Tokens"},
     *      summary="Get latest transactions for a token",
     *      description="Returns the latest transactions for a given token address",
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
     *              @OA\Property(property="address", type="string", example="0x123456789abcdef..."),
     *              @OA\Property(
     *                  property="transactions",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="hash", type="string", example="0xabcdef1234567890..."),
     *                      @OA\Property(property="from", type="string", example="0x1111222233334444..."),
     *                      @OA\Property(property="to", type="string", example="0x5555666677778888..."),
     *                      @OA\Property(property="value", type="string", example="1000000000000000000"),
     *                      @OA\Property(property="timestamp", type="integer", example=1621234567)
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
    public function getLatestTransactions(string $address): JsonResponse
    {
        return CachedApiCallHelper::cachedApiCall("transactions_{$address}", 5, function () use ($address) {
            $latestTransactions = $this->blockchainService->getLatestTransactions($address);

            if ($latestTransactions->status() === 200) {
                $data = $latestTransactions->getData(true);
                $this->storeTokenTransactions($data['transactions'] ?? []);
            }

            return $latestTransactions;
        });
    }

    private function storeTokenTransactions(array $transactions): void
    {
        $transactionsData = array_map(function ($transaction){
            return [
                'hash' => $transaction['hash'],
                'from_address' => $transaction['from'],
                'to_address' => $transaction['to'],
                'value' => $transaction['value'],
                'timestamp' => $transaction['timestamp'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $transactions);

        Transaction::insertOrIgnore($transactionsData);
    }

}