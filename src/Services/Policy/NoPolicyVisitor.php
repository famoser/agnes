<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services\Policy;

use Agnes\Models\Task\AbstractTask;
use Symfony\Component\Console\Style\StyleInterface;

class NoPolicyVisitor extends AbstractPolicyVisitor
{
    /**
     * PolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, AbstractTask $task)
    {
        parent::__construct($io, $task);
    }
}
