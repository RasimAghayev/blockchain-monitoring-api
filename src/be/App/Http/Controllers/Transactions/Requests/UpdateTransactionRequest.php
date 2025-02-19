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
 *      @OA\Property(
 *               property="name",
 *               type="string",
 *               example="John Doe"
 *       ),
 *      @OA\Property(
 *               property="type",
 *               type="integer",
 *               example=1,
 *               description="1 for Personal, 2 for Business"
 *      )
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
            foreach ($rules as $field => &$fieldRules) {
                if (!is_array($fieldRules)) {
                    $fieldRules = [$fieldRules];
                }

                if (!in_array('required', $fieldRules)) {
                    array_unshift($fieldRules, 'required');
                }
            }
        } else {
            foreach ($rules as $field => &$fieldRules) {
                if (!is_array($fieldRules)) {
                    $fieldRules = [$fieldRules];
                }
                array_unshift($fieldRules, 'sometimes');
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
     * @return TransactionDTO
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
     * @return Transaction
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