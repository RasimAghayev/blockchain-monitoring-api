<?php

namespace App\Http\Controllers\Tokens\Resources;

use App\Http\Controllers\Tokens\Traits\TokenPaginationLinksTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="TokenCollection",
 *     title="Token Collection",
 *     description="Token collection",
 *       @OA\Property(
 *           property="message",
 *           type="string",
 *           example="Token successfully full list."
 *       ),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/TokenResource")
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
 *         @OA\Property(property="self", type="string", example="http://example.com/api/tokens?page=1"),
 *         @OA\Property(property="first", type="string", example="http://example.com/api/tokens?page=1"),
 *         @OA\Property(property="last", type="string", example="http://example.com/api/tokens?page=4"),
 *         @OA\Property(property="prev", type="string", nullable=true),
 *         @OA\Property(property="next", type="string", example="http://example.com/api/tokens?page=2")
 *     )
 * )
 */
class TokenCollection extends ResourceCollection
{
    use TokenPaginationLinksTrait;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = TokenResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => 'Token successfully full list.',
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