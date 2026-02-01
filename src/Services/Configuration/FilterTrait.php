<?php

namespace Agnes\Services\Configuration;

use Agnes\Models\Filter;

trait FilterTrait
{
    /**
     * @var Filter|null
     */
    private $filter;

    public function getFilter(): ?Filter
    {
        return $this->filter;
    }
}
