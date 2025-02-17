<?php

namespace app\Http\Responses;

class ErrorTokenBlacklisted extends ApiErrorResponse
{
    protected function defaultResponseCode(): int
    {
        return 401;
    }

    protected function defaultErrorMessage(): string
    {
        return 'Token is blacklisted.';
    }
}
