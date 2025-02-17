<?php

namespace App\Http\Controllers\Tokens\Observers;

use App\Http\Controllers\DIM\Models\DIM;
use App\Http\Controllers\Tokens\Models\Token;

class TokenObserver
{
    public function updated(Token $token)
    {
        $this->created($token);
    }

    public function created(Token $token)
    {
        DIM::updateOrCreate(
            [
                'token_id' => $token->id,
            ],
            [
                'token_address' => $token->address,
                'token_name' => $token->name,
                'token_symbol' => $token->symbol,
                'token_total_supply' => $token->total_supply,
            ]
        );
    }

    public function deleted(Token $token)
    {
        DIM::where('token_id', $token->id)->delete();
    }
}