<?php

namespace App\Http\Controllers\Tokens\Resources;

use App\Http\Controllers\Tokens\DTOs\TokenDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TokenResource",
 *     title="Token Resource",
 *     description="Token resource representation",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(
 *         property="tokenAddress",
 *         type="string",
 *         example="0x1234567890abcdef1234567890abcdef12345678",
 *         description="The unique blockchain address of the token"
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
 *     ),
 *     @OA\Property(
 *         property="createdAt",
 *         type="string",
 *         format="date-time",
 *         example="2023-01-01 00:00:00",
 *         description="Timestamp when the token was created"
 *     ),
 *     @OA\Property(
 *         property="updatedAt",
 *         type="string",
 *         format="date-time",
 *         example="2023-01-02 12:30:00",
 *         description="Timestamp when the token was last updated"
 *     )
 * )
 */
class TokenResource extends JsonResource
{
    /**
     * @var TokenDTO|null
     */
    private ?TokenDTO $tokenDTO = null;

    /**
     * @param TokenDTO $tokenDTO
     * @return $this
     */
    public function setDTO(TokenDTO $tokenDTO): self
    {
        $this->tokenDTO = $tokenDTO;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'tokenAddress' => $this->address,
            'tokenName' => $this->name,
            'tokenSymbol' => $this->symbol,
            'tokenTotalSupply' => $this->total_supply,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}