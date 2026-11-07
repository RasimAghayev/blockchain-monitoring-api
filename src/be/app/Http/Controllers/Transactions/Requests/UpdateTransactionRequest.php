<?php

declare(strict_types=1);

namespace App\Http\Controllers\Transactions\Requests;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Models\Transaction;
use App\Http\Controllers\Transactions\Traits\TransactionRequestTrait;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateTransactionRequest",
 *     title="Update Transaction Request",
 *     description="Update Transaction request body data",
 *     type="object",
 *
 *      @OA\Property(
 *               property="transactionHash",
 *               type="string",
 *               example="0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345"
 *       ),
 *      @OA\Property(
 *               property="transactionFromAddress",
 *               type="string",
 *               example="0x1111111111111111111111111111111111111111"
 *       ),
 *      @OA\Property(
 *               property="transactionToAddress",
 *               type="string",
 *               example="0x2222222222222222222222222222222222222222"
 *       ),
 *      @OA\Property(
 *               property="transactionAmount",
 *               type="number",
 *               format="double",
 *               example=1.5
 *       ),
 *      @OA\Property(
 *               property="transactionGasFee",
 *               type="number",
 *               format="double",
 *               example=0.0004
 *       )
 * )
 */
class UpdateTransactionRequest extends ApiFormRequest
{
    use TransactionRequestTrait;

    private ?Transaction $transaction = null;

    /**
     * Get validation rules
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = $this->getCommonRules();

        if ($this->method() === 'PUT') {
            foreach ($this->getRequiredFields() as $field) {
                if (isset($rules[$field]) && is_array($rules[$field])) {
                    array_unshift($rules[$field], 'required');
                }
            }
        } else {
            foreach (array_keys($rules) as $field) {
                $rules[$field] = array_merge(['sometimes'], (array) $rules[$field]);
            }
        }

        return $rules;
    }

    /**
     * Get custom validation messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->getCommonMessages();
    }

    /**
     * Convert validated data to DTO
     *
     * @throws ModelNotFoundException
     */
    public function toDTO(): TransactionDTO
    {
        $validatedData = $this->validated();

        if ($this->method() === 'PATCH') {
            try {
                $transaction = $this->getTransaction();
                $existingData = $transaction->toArray();

                foreach ($validatedData as $key => $value) {
                    if (isset($value)) {
                        $existingData[$key] = $value;
                    }
                }
                $validatedData = $existingData;
            } catch (ModelNotFoundException $e) {
                throw new ModelNotFoundException('Transaction not found');
            }
        }

        return TransactionDTO::fromRequest($validatedData);
    }

    /**
     * Get transaction instance
     *
     * @throws ModelNotFoundException
     */
    protected function getTransaction(): Transaction
    {
        if ($this->transaction === null) {
            $transactionId = $this->route('id');
            $this->transaction = Transaction::findOrFail($transactionId);
        }

        return $this->transaction;
    }
}
