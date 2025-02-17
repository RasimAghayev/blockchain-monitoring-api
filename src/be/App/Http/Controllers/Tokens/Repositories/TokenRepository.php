<?php

namespace App\Http\Controllers\Tokens\Repositories;

use App\Http\Controllers\Tokens\{DTOs\TokenDTO, Models\Token};
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class TokenRepository implements TokenRepositoryInterface
{
    public function __construct(
        protected Token $tokenModel
    )
    {
    }

    /**
     * Get filtered tokens with pagination
     *
     * @param array $queryItems
     * @param bool $includeTags
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFilteredTokens(array $queryItems,
                                      bool  $includeTags,
                                      int   $perPage): LengthAwarePaginator
    {
        $query = $this->tokenModel->where($queryItems);

        $maxPerPage = 100;
        $perPage = min($perPage, $maxPerPage);

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Create new token
     *
     * @param TokenDTO $tokenDTO
     * @return Token
     */
    public function create(TokenDTO $tokenDTO): Token
    {
        return $this->tokenModel->create($tokenDTO->toArray());
    }

    /**
     * Update existing token
     *
     * @param int $id
     * @param TokenDTO $tokenDTO
     * @return Token
     */
    public function update(int $id, TokenDTO $tokenDTO): Token
    {
        $token = $this->findOrFail($id);
        $token->update($tokenDTO->toArray());

        return $token->fresh();
    }

    /**
     * Find token by ID
     *
     * @param int $id
     * @return Token|string
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Token|string
    {
        $token = $this->tokenModel->find($id);

        if (!$token) {
            return "Token not found with ID: {$id}";
        }

        return $token;
    }

    /**
     * Delete token
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $token = $this->findOrFail($id);
        $token->delete();
    }
}