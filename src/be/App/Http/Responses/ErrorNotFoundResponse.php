<?php

namespace App\Http\Responses;

class ErrorNotFoundResponse extends ApiErrorResponse
{
    protected function defaultResponseCode(): int
    {
        return 404;
    }

    protected function defaultErrorMessage(): string
    {
        return 'Not found.';
    }
}
