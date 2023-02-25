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

class Build extends AbstractTask
{
    public const TYPE = 'build';

    /**
     * @var string
     */
    private $commitish;

    /**
     * Build constructor.
     */
    public function __construct(string $commtish)
    {
        $this->commitish = $commtish;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitBuild($this);
    }

    public function describe(): string
    {
        return 'build '.$this->commitish;
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
