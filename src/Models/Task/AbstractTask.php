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

abstract class AbstractTask
{
    /**
     * @throws \Exception
     */
    abstract public function accept(AbstractTaskVisitor $abstractActionVisitor);

    abstract public function describe(): string;

    abstract public function type(): string;
}
