<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tokens\Services;

use App\Http\Controllers\Tokens\DTOs\TokenDTO;
use App\Http\Controllers\Tokens\Filters\TokenFilters;
use App\Http\Controllers\Tokens\Models\Token;
use App\Http\Controllers\Tokens\Repositories\TokenRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TokenService implements TokenServiceInterface
{
    public function __construct(
        protected TokenRepositoryInterface $tokenRepository
    ) {}

    /**
     * Get filtered tokens with pagination
     */
    public function getTokens(Request $request, bool $includeTags, int $perPage): LengthAwarePaginator
    {
        $filter = new TokenFilters;
        $queryItems = $filter->transform($request);

        return $this->tokenRepository->getFilteredTokens(
            queryItems: $queryItems,
            includeTags: $includeTags,
            perPage: $perPage
        );
    }

    public function createToken(TokenDTO $tokenDTO): Token
    {
        return DB::transaction(function () use ($tokenDTO) {
            return $this->tokenRepository->create($tokenDTO);
        });
    }

    public function getTokenById(int $id): Token
    {
        return $this->tokenRepository->findOrFail($id);
    }

    public function updateToken(int $id, TokenDTO $tokenDTO): Token
    {
        return DB::transaction(function () use ($id, $tokenDTO) {
            return $this->tokenRepository->update($id, $tokenDTO);
        });
    }

    public function deleteToken(int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->tokenRepository->delete($id);
        });
    }
}
