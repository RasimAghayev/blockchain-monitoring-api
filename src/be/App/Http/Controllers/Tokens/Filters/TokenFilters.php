<?php

namespace App\Http\Controllers\Tokens\Filters;

use App\Filters\ApiFilter;

class TokenFilters extends ApiFilter
{
    protected array $safeParms = [
        'tokenAddress' => ['eq', 'lk', 'nlk', 'ilk', 'inlk'],
        'tokenName' => ['eq', 'lk', 'nlk', 'ilk', 'inlk'],
        'tokenSymbol' => ['eq', 'lk', 'nlk', 'ilk', 'inlk'],
        'tokenTotalSupply' => ['eq', 'lt', 'lte', 'gt', 'gte', 'ne', 'bt', 'nbt', 'in', 'nin'],
    ];
    protected array $columnMap = [
        'tokenAddress' => 'address',
        'tokenName' => 'name',
        'tokenSymbol' => 'symbol',
        'tokenTotalSupply' => 'total_supply',
    ];
}