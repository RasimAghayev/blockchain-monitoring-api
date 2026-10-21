<?php

namespace App\Http\Controllers\Tokens\Repositories;

use App\Http\Controllers\Tokens\DTOs\TokenDTO;
use App\Http\Controllers\Tokens\Models\Token;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class TokenRepository implements TokenRepositoryInterface
{
    public function __construct(
        protected Token $tokenModel
    ) {}

    /**
     * Get filtered tokens with pagination
     */
    public function getFilteredTokens(array $queryItems,
        bool $includeTags,
        int $perPage): LengthAwarePaginator
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
     */
    public function create(TokenDTO $tokenDTO): Token
    {
        return $this->tokenModel->create($tokenDTO->toArray());
    }

    /**
     * Update existing token
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
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Token
    {
        $token = $this->tokenModel->find($id);

        if (! $token) {
            throw new ModelNotFoundException("Token not found with ID: {$id}");
        }

        return $token;
    }

    /**
     * Delete token
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }
}
