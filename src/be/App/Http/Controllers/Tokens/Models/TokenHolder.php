<?php

namespace App\Http\Controllers\Tokens\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static findOrFail(mixed $token_id)
 */
class TokenHolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_address',
        'holder_address',
        'balance',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'float',
            'percentage' => 'float',
        ];
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class, 'token_address', 'address');
    }
}
