<?php

namespace App\Http\Controllers\Tokens\Services;

use App\Http\Controllers\Tokens\DTOs\TokenDTO;
use App\Http\Controllers\Tokens\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface TokenServiceInterface
{
    /**
     * @param Request $request
     * @param bool $includeTags
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTokens(Request $request,
                              bool    $includeTags,
                              int     $perPage): LengthAwarePaginator;

    /**
     * @param TokenDTO $tokenDTO
     * @return Token
     */
    public function createToken(TokenDTO $tokenDTO): Token;

    /**
     * @param int $id
     * @return Token|string
     */
    public function getTokenById(int $id): Token|string;

    /**
     * @param int $id
     * @param TokenDTO $tokenDTO
     * @return Token
     */
    public function updateToken(int $id, TokenDTO $tokenDTO): Token;

    /**
     * @param int $id
     * @return void
     */
    public function deleteToken(int $id): void;

}