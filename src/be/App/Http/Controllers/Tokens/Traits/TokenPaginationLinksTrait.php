<?php

namespace App\Http\Controllers\Tokens\Traits;

use App\Traits\PaginationLinksTrait;

trait TokenPaginationLinksTrait
{
    use PaginationLinksTrait;

    /**
     * @return array
     */
    protected function getMetaData(): array
    {
        return [
            'current_page' => $this->resource->currentPage(),
            'from' => $this->resource->firstItem(),
            'last_page' => $this->resource->lastPage(),
            'per_page' => $this->resource->perPage(),
            'to' => $this->resource->lastItem(),
            'total' => $this->resource->total(),
        ];
    }
}