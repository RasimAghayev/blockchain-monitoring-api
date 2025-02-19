<?php

namespace App\Http\Controllers\Transactions\Models;

use App\Http\Controllers\Orders\Models\Order;
use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, Relations\HasOne};

/**
 * @method static findOrFail(mixed $transaction_id)
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        "hash",
        "from_address",
        "to_address",
        "amount",
        "gas_fee",
    ];
    protected function casts(): array
    {
        return [
            'amount' => 'numeric',
            'gas_fee' => 'numeric',
        ];
    }
}
