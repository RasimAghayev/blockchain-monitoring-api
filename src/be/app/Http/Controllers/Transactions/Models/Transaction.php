<?php

namespace App\Http\Controllers\Transactions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static findOrFail(mixed $transaction_id)
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'hash',
        'from_address',
        'to_address',
        'amount',
        'gas_fee',
    ];

    protected function casts(): array
    {
        return [
            // "numeric" is not a valid Eloquent cast. Every read and write of this
            // model threw InvalidCastException. The columns are decimal(30,10), and
            // the sibling Token model casts its decimal to float.
            'amount' => 'float',
            'gas_fee' => 'float',
        ];
    }
}
