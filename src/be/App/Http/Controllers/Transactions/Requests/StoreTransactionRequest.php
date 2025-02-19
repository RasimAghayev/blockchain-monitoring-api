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
 *     required={"name", "type"},
 *     @OA\Property(
 *              property="name",
 *              type="string",
 *              example="John Doe"
 *      ),
 *     @OA\Property(
 *              property="type",
 *              type="integer",
 *              example=1,
 *              description="1 for Personal, 2 for Business"
 *     )
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
        $requiredFields = ['name', 'type'];
        foreach ($requiredFields as $field) {
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

    /**
     * @return TransactionDTO
     */
    public function toDTO(): TransactionDTO
    {
        return TransactionDTO::fromRequest($this->validated());
    }
}