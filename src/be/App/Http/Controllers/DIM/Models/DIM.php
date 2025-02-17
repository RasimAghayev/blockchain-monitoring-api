<?php

namespace App\Http\Controllers\DIM\Models;

use App\Http\Controllers\Tokens\Models\Token;
use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, Relations\BelongsTo};

/**
 * @method static where(string $string, mixed $id)
 */
class DIM extends Model
{
    use HasFactory;

    protected $table = 'dim_table';
    protected $fillable = [
        "token_id",
        "token_address",
        "token_name",
        "token_symbol",
        "token_total_supply",
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    protected function casts(): array
    {
        return [
            'order_price' => 'float',
        ];
    }
}
