<?php

namespace App\Http\Controllers\Transactions\Resources;

use App\Http\Controllers\Transactions\Traits\TransactionPaginationLinksTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TransactionCollection",
 *     title="Transaction Collection",
 *     description="Transaction collection",
 *       @OA\Property(
 *           property="message",
 *           type="string",
 *           example="Transaction successfully full list."
 *       ),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/TransactionResource")
 *     ),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="from", type="integer", example=1),
 *         @OA\Property(property="last_page", type="integer", example=1),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="to", type="integer", example=15),
 *         @OA\Property(property="total", type="integer", example=50)
 *     ),
 *     @OA\Property(
 *         property="links",
 *         type="object",
 *         @OA\Property(property="self", type="string", example="http://example.com/api/transactions?page=1"),
 *         @OA\Property(property="first", type="string", example="http://example.com/api/transactions?page=1"),
 *         @OA\Property(property="last", type="string", example="http://example.com/api/transactions?page=4"),
 *         @OA\Property(property="prev", type="string", nullable=true),
 *         @OA\Property(property="next", type="string", example="http://example.com/api/transactions?page=2")
 *     )
 * )
 */
class TransactionCollection extends ResourceCollection
{
    use TransactionPaginationLinksTrait;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = TransactionResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => 'Transaction successfully full list.',
            'data' => $this->collection,
            'meta' => $this->getMetaData(),
            'links' => [
                'self' => $this->getSelfLink(),
                'first' => $this->getFirstLink(),
                'last' => $this->getLastLink(),
                'prev' => $this->getPrevLink(),
                'next' => $this->getNextLink(),
            ],
        ];
    }

}