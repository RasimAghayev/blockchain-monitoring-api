<?php

namespace App\Http\Controllers\Transactions\Filters;

use App\Filters\ApiFilter;

class TransactionFilters extends ApiFilter
{
    protected array $safeParms = [
        'transactionHash' => ['eq', 'lk', 'nlk', 'ilk', 'inlk'],
        'transactionFromAddress' => ['eq', 'lk', 'nlk', 'ilk', 'inlk'],
        'transactionToAddress' => ['eq', 'lk', 'nlk', 'ilk', 'inlk'],
        'transactionAmount' => ['eq', 'lt', 'lte', 'gt', 'gte', 'ne', 'bt', 'nbt', 'in', 'nin'],
    ];
    protected array $columnMap = [
        'transactionHash' => 'hash',
        'transactionFromAddress' => 'from_address',
        'transactionToAddress' => 'to_address',
        'transactionAmount' => 'amount',
    ];
}