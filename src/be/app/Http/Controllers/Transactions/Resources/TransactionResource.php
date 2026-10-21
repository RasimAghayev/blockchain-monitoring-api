<?php

namespace App\Http\Controllers\Transactions\Resources;

use App\Http\Controllers\Transactions\Enums\TransactionTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TransactionResource",
 *     title="Transaction Resource",
 *     description="A transaction stored by the monitoring service",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(
 *         property="transactionHash",
 *         type="string",
 *         example="0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345",
 *         description="The unique hash of the transaction on the chain"
 *     ),
 *     @OA\Property(
 *         property="transactionFromAddress",
 *         type="string",
 *         example="0x1111111111111111111111111111111111111111",
 *         description="The address the transaction was sent from"
 *     ),
 *     @OA\Property(
 *         property="transactionToAddress",
 *         type="string",
 *         example="0x2222222222222222222222222222222222222222",
 *         description="The address the transaction was sent to. Empty for a contract deployment"
 *     ),
 *     @OA\Property(
 *         property="transactionAmount",
 *         type="number",
 *         format="double",
 *         example=1.5,
 *         description="The transferred amount"
 *     ),
 *     @OA\Property(
 *         property="transactionGasFee",
 *         type="number",
 *         format="double",
 *         example=0.0004,
 *         description="The gas fee paid for the transaction"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="object",
 *         @OA\Property(property="value", type="integer", example=1),
 *         @OA\Property(property="label", type="string", example="Transfer")
 *     ),
 *     @OA\Property(property="createdAt", type="string", format="date-time", example="2023-01-01 00:00:00"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", example="2023-01-01 00:00:00")
 * )
 */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = TransactionTypes::forRecipient($this->to_address);

        return [
            'id' => $this->id,
            'transactionHash' => $this->hash,
            'transactionFromAddress' => $this->from_address,
            'transactionToAddress' => $this->to_address,
            'transactionAmount' => $this->amount,
            'transactionGasFee' => $this->gas_fee,
            'type' => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
