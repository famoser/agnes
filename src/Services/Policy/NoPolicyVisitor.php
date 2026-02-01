<?php

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
