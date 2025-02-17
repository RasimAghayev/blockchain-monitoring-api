<?php

namespace App\Traits;

trait PaginationLinksTrait
{

    protected function getSelfLink(): ?string
    {
        return $this->resource->url($this->resource->currentPage());
    }

    protected function getFirstLink(): ?string
    {
        return $this->resource->url(1);
    }

    protected function getLastLink(): ?string
    {
        return $this->resource->url($this->resource->lastPage());
    }

    protected function getPrevLink(): ?string
    {
        return $this->resource->previousPageUrl();
    }

    protected function getNextLink(): ?string
    {
        return $this->resource->nextPageUrl();
    }

}