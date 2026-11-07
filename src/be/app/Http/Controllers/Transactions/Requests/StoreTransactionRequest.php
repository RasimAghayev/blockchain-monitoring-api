<?php

declare(strict_types=1);

namespace App\Http\Controllers\Transactions\Requests;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Traits\TransactionRequestTrait;
use App\Http\Requests\ApiFormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StoreTransactionRequest",
 *     title="Store Transaction Request",
 *     description="Store Transaction request body data",
 *     type="object",
 *     required={"transactionHash", "transactionFromAddress", "transactionAmount", "transactionGasFee"},
 *
 *     @OA\Property(
 *              property="transactionHash",
 *              type="string",
 *              example="0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345"
 *      ),
 *     @OA\Property(
 *              property="transactionFromAddress",
 *              type="string",
 *              example="0x1111111111111111111111111111111111111111"
 *      ),
 *     @OA\Property(
 *              property="transactionToAddress",
 *              type="string",
 *              example="0x2222222222222222222222222222222222222222",
 *              description="Empty for a contract deployment, which has no recipient"
 *      ),
 *     @OA\Property(
 *              property="transactionAmount",
 *              type="number",
 *              format="double",
 *              example=1.5
 *      ),
 *     @OA\Property(
 *              property="transactionGasFee",
 *              type="number",
 *              format="double",
 *              example=0.0004
 *      )
 * )
 */
class StoreTransactionRequest extends ApiFormRequest
{
    use TransactionRequestTrait;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = $this->getCommonRules();

        // Add required rules for specific fields
        foreach ($this->getRequiredFields() as $field) {
            if (isset($rules[$field]) && is_array($rules[$field])) {
                array_unshift($rules[$field], 'required');
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return $this->getCommonMessages();
    }

    public function toDTO(): TransactionDTO
    {
        return TransactionDTO::fromRequest($this->validated());
    }
}
