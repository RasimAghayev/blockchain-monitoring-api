<?php

namespace App\Http\Controllers\Tokens\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, Relations\BelongsTo};

/**
 * @method static findOrFail(mixed $token_id)
 */
class TokenHolder extends Model
{
    use HasFactory;

    protected $fillable = [
        "token_address",
        "holder_address",
        "balance",
        "percentage"
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'float',
            'percentage' => 'float',
        ];
    }


    /**
     * @return BelongsTo
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class, 'token_address', 'address');
    }

}
