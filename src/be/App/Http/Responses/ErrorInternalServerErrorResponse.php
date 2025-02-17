<?php

namespace App\Http\Responses;

class ErrorInternalServerErrorResponse extends ApiErrorResponse
{
    protected function defaultResponseCode(): int
    {
        return 500;
    }

    protected function defaultErrorMessage(): string
    {
        return 'There was an error with your request. Please try again later.';
    }
}