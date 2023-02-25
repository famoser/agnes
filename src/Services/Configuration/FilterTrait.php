<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
