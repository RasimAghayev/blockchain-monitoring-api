<?php
declare(strict_types=1);

namespace App\Http\Controllers\Tokens\Requests;

use App\Http\Controllers\Tokens\DTOs\TokenDTO;
use App\Http\Controllers\Tokens\Traits\TokenRequestTrait;
use App\Http\Requests\ApiFormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StoreTokenRequest",
 *     title="Store Token Request",
 *     description="Store Token request body data",
 *     type="object",
 *     required={"tokenAddress", "tokenName", "tokenSymbol", "tokenTotalSupply"},
 *
 *     @OA\Property(
 *         property="tokenAddress",
 *         type="string",
 *         example="0x1234567890abcdef1234567890abcdef12345678",
 *         description="The unique address of the token"
 *     ),
 *     @OA\Property(
 *         property="tokenName",
 *         type="string",
 *         example="MyToken",
 *         description="The name of the token"
 *     ),
 *     @OA\Property(
 *         property="tokenSymbol",
 *         type="string",
 *         example="MTK",
 *         description="The symbol of the token"
 *     ),
 *     @OA\Property(
 *         property="tokenTotalSupply",
 *         type="number",
 *         format="decimal",
 *         example=1000000.50,
 *         description="Total supply of the token (decimal value supported)"
 *     )
 * )
 */
class StoreTokenRequest extends ApiFormRequest
{
    use TokenRequestTrait;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = $this->getCommonRules();

        // Add required rules for specific fields
        $requiredFields = ['tokenAddress', 'tokenName', 'tokenSymbol', 'tokenTotalSupply'];
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
     * @return TokenDTO
     */
    public function toDTO(): TokenDTO
    {
        return TokenDTO::fromRequest($this->validated());
    }
}