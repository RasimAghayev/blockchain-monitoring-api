<?php

namespace App\Http\Controllers\Tokens\Repositories;

use App\Http\Controllers\Tokens\{DTOs\TokenDTO, Models\Token};
use Illuminate\Pagination\LengthAwarePaginator;

interface TokenRepositoryInterface
{
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
                                      int   $perPage): LengthAwarePaginator;

    /**
     * Create new token
     *
     * @param TokenDTO $tokenDTO
     * @return Token
     */
    public function create(TokenDTO $tokenDTO): Token;

    /**
     * Find token by ID
     *
     * @param int $id
     * @return Token|string
     */
    public function findOrFail(int $id): Token|string;

    /**
     * Update existing token
     *
     * @param int $id
     * @param TokenDTO $tokenDTO
     * @return Token
     */
    public function update(int $id, TokenDTO $tokenDTO): Token;

    /**
     * Delete token
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void;

}