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

class File
{
    /**
     * @var bool
     */
    private $required;

    /**
     * @var string
     */
    private $path;

    /**
     * File constructor.
     */
    public function __construct(bool $required, string $path)
    {
        $this->required = $required;
        $this->path = $path;
    }

    public function getIsRequired(): bool
    {
        return $this->required;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
