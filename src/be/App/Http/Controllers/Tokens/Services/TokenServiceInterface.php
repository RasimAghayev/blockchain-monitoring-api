<?php

namespace App\Http\Controllers\Tokens\Services;

use App\Http\Controllers\Tokens\DTOs\TokenDTO;
use App\Http\Controllers\Tokens\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface TokenServiceInterface
{
    public function getTokens(Request $request,
        bool $includeTags,
        int $perPage): LengthAwarePaginator;

    public function createToken(TokenDTO $tokenDTO): Token;

    public function getTokenById(int $id): Token|string;

    public function updateToken(int $id, TokenDTO $tokenDTO): Token;

    public function deleteToken(int $id): void;
}
