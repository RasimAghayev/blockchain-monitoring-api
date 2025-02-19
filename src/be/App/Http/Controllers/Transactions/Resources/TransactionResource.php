<?php

namespace App\Http\Controllers\Transactions\Resources;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Enums\TransactionTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TransactionResource",
 *     title="Transaction Resource",
 *     description="Transaction resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(
 *         property="type",
 *         type="object",
 *         @OA\Property(property="value", type="integer", example=1),
 *         @OA\Property(property="label", type="string", example="Fərdi")
 *     ),
 *     @OA\Property(property="createdAt", type="string", format="date-time", example="2023-01-01 00:00:00"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", example="2023-01-01 00:00:00")
 * )
 */
class TransactionResource extends JsonResource
{
    /**
     * @var TransactionDTO|null
     */
    private ?TransactionDTO $transactionDTO = null;

    /**
     * @param TransactionDTO $transactionDTO
     * @return $this
     */
    public function setDTO(TransactionDTO $transactionDTO): self
    {
        $this->transactionDTO = $transactionDTO;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->type;

        // Handle different possible type scenarios
        $status = match (true) {
            $type instanceof TransactionTypes => $type,
            is_string($type) => TransactionTypes::tryFrom($type),
            is_int($type) => TransactionTypes::tryFrom($type),
            default => null
        };

        // Fallback to PERSONAL if type is invalid
        if (!$status) {
            $status = TransactionTypes::PERSONAL;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}