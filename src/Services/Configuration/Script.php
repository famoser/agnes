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

class Script
{
    use FilterTrait;
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $script;

    /**
     * Script constructor.
     */
    public function __construct(string $name, array $script, ?Filter $filter)
    {
        $this->name = $name;
        $this->script = $script;
        $this->filter = $filter;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getScript(): array
    {
        return $this->script;
    }
}
