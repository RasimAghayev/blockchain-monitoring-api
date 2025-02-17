<?php
declare(strict_types=1);

namespace App\Http\Controllers\Tokens\Requests;

use App\Http\Controllers\Tokens\DTOs\TokenDTO;
use App\Http\Controllers\Tokens\Models\Token;
use App\Http\Controllers\Tokens\Traits\TokenRequestTrait;
use App\Http\Requests\ApiFormRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateTokenRequest",
 *     title="Update Token Request",
 *     description="Update Token request body data",
 *      type="object",
 *      required={"tokenAddress", "tokenName", "tokenSymbol", "tokenTotalSupply"},
 *      @OA\Property(
 *          property="tokenAddress",
 *          type="string",
 *          example="0x1234567890abcdef1234567890abcdef12345678",
 *          description="The unique address of the token"
 *      ),
 *      @OA\Property(
 *          property="tokenName",
 *          type="string",
 *          example="MyToken",
 *          description="The name of the token"
 *      ),
 *      @OA\Property(
 *          property="tokenSymbol",
 *          type="string",
 *          example="MTK",
 *          description="The symbol of the token"
 *      ),
 *      @OA\Property(
 *          property="tokenTotalSupply",
 *          type="number",
 *          format="decimal",
 *          example=1000000.50,
 *          description="Total supply of the token (decimal value supported)"
 *      )
 *  )
 */
class UpdateTokenRequest extends ApiFormRequest
{
    use TokenRequestTrait;

    private ?Token $token = null;

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
     * @return TokenDTO
     * @throws ModelNotFoundException
     */
    public function toDTO(): TokenDTO
    {
        $validatedData = $this->validated();

        if ($this->method() === 'PATCH') {
            try {
                $token = $this->getToken();
                $existingData = $token->toArray();

                foreach ($validatedData as $key => $value) {
                    if (isset($value)) {
                        $existingData[$key] = $value;
                    }
                }
                $validatedData = $existingData;
            } catch (ModelNotFoundException $e) {
                throw new ModelNotFoundException('Token not found');
            }
        }

        return TokenDTO::fromRequest($validatedData);
    }

    /**
     * Get token instance
     *
     * @return Token
     * @throws ModelNotFoundException
     */
    protected function getToken(): Token
    {
        if ($this->token === null) {
            $tokenId = $this->route('id');
            $this->token = Token::findOrFail($tokenId);
        }

        return $this->token;
    }

}