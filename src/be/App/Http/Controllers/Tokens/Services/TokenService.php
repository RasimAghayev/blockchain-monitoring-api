<?php
declare(strict_types=1);

namespace App\Http\Controllers\Tokens\Services;

use App\Http\Controllers\Tokens\{DTOs\TokenDTO,
    Filters\TokenFilters,
    Models\Token,
    Repositories\TokenRepositoryInterface};
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TokenService implements TokenServiceInterface
{
    /**
     * @param TokenRepositoryInterface $tokenRepository
     */
    public function __construct(
        protected TokenRepositoryInterface $tokenRepository
    )
    {
    }

    /**
     * Get filtered tokens with pagination
     *
     * @param Request $request
     * @param bool $includeTags
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTokens(Request $request, bool $includeTags, int $perPage): LengthAwarePaginator
    {
        $filter = new TokenFilters();
        $queryItems = $filter->transform($request);

        return $this->tokenRepository->getFilteredTokens(
            queryItems: $queryItems,
            includeTags: $includeTags,
            perPage: $perPage
        );
    }

    /**
     * @param TokenDTO $tokenDTO
     * @return Token
     */
    public function createToken(TokenDTO $tokenDTO): Token
    {
        return DB::transaction(function () use ($tokenDTO) {
            return $this->tokenRepository->create($tokenDTO);
        });
    }

    /**
     * @param int $id
     * @return Token|string
     */
    public function getTokenById(int $id): Token|string
    {
        return $this->tokenRepository->findOrFail($id);
    }

    /**
     * @param int $id
     * @param TokenDTO $tokenDTO
     * @return Token
     */
    public function updateToken(int $id, TokenDTO $tokenDTO): Token
    {
        return DB::transaction(function () use ($id, $tokenDTO) {
            return $this->tokenRepository->update($id, $tokenDTO);
        });
    }

    /**
     * @param int $id
     * @return void
     */
    public function deleteToken(int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->tokenRepository->delete($id);
        });
    }

}