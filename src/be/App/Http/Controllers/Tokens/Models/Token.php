<?php

namespace App\Http\Controllers\Tokens\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model};

/**
 * @method static findOrFail(mixed $token_id)
 */
class Token extends Model
{
    use HasFactory;

    protected $fillable = [
        "address",
        "name",
        "symbol",
        "total_supply"
    ];

    protected function casts(): array
    {
        return [
            'total_supply' => 'float',
        ];
    }

}
