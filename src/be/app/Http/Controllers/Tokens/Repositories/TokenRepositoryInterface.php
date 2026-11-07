<?php

namespace App\Http\Controllers\Tokens\Repositories;

use App\Http\Controllers\Tokens\DTOs\TokenDTO;
use App\Http\Controllers\Tokens\Models\Token;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

interface TokenRepositoryInterface
{
    /**
     * Get filtered tokens with pagination
     */
    public function getFilteredTokens(array $queryItems,
        bool $includeTags,
        int $perPage): LengthAwarePaginator;

    /**
     * Create new token
     */
    public function create(TokenDTO $tokenDTO): Token;

    /**
     * Find token by ID
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Token;

    /**
     * Update existing token
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, TokenDTO $tokenDTO): Token;

    /**
     * Delete token
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): void;
}
