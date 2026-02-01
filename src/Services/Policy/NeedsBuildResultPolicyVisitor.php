<?php

namespace Agnes\Services\Policy;

use Agnes\Models\Task\AbstractTask;
use Agnes\Services\Task\ExecutionVisitor\BuildResult;
use Symfony\Component\Console\Style\StyleInterface;

class NeedsBuildResultPolicyVisitor extends NoPolicyVisitor
{
    private StyleInterface $io;

    private AbstractTask $task;

    /**
     * DeployPolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, private ?BuildResult $buildResult, AbstractTask $task)
    {
        parent::__construct($io, $task);
        $this->io = $io;
        $this->task = $task;
    }

    public function validate(): bool
    {
        if (null === $this->buildResult) {
            $this->io->error('To ' . $this->task->describe() . ' a successful build it required.');

            return false;
        }

        return parent::validate();
    }
}
