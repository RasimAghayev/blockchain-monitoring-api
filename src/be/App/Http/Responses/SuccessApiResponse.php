<?php

namespace App\Http\Responses;

class SuccessApiResponse extends ApiBaseResponse
{
    protected function defaultResponseCode(): int
    {
        return 200;
    }
}