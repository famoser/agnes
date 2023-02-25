<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

class Release extends AbstractTask
{
    public const TYPE = 'release';

    /**
     * @var string
     */
    private $name;

    /**
     * Release constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function describe(): string
    {
        return 'release '.$this->getName();
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitRelease($this);
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
