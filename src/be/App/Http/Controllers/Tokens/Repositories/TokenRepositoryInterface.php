<?php

namespace App\Http\Controllers\Tokens\Repositories;

use App\Http\Controllers\Tokens\DTOs\TokenDTO;
use App\Http\Controllers\Tokens\Models\Token;
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
     */
    public function findOrFail(int $id): Token|string;

    /**
     * Update existing token
     */
    public function update(int $id, TokenDTO $tokenDTO): Token;

    /**
     * Delete token
     */
    public function delete(int $id): void;
}
