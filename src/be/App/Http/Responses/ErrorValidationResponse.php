<?php

namespace App\Http\Responses;

class ErrorValidationResponse extends ApiErrorResponse
{
    protected function defaultResponseCode(): int
    {
        return 422;
    }

    protected function defaultErrorMessage(): string
    {
        return 'Validation error.';
    }
}