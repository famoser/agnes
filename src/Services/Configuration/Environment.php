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

class Environment
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $stages;

    /**
     * Environment constructor.
     *
     * @param string[] $stages
     */
    public function __construct(string $name, array $stages)
    {
        $this->name = $name;
        $this->stages = $stages;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getStages(): array
    {
        return $this->stages;
    }
}
