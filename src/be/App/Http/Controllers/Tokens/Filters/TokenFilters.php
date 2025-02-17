<?php

namespace App\Http\Controllers\Tokens\Filters;

use App\Filters\ApiFilter;

class TokenFilters extends ApiFilter
{
    protected array $safeParms = [
        'address' => ['eq', 'lk', 'nlk', 'ilk', 'inlk'],
        'name' => ['eq', 'lk', 'nlk', 'ilk', 'inlk'],
        'symbol' => ['eq', 'lk', 'nlk', 'ilk', 'inlk'],
        'total_supply' => ['eq', 'lt', 'lte', 'gt', 'gte', 'ne', 'bt', 'nbt', 'in', 'nin'],
    ];
}