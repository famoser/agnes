<?php

namespace Agnes\Services\Policy;

use Agnes\Models\Task\AbstractTask;
use Agnes\Services\Task\ExecutionVisitor\BuildResult;
use Symfony\Component\Console\Style\StyleInterface;

class NeedsBuildResultPolicyVisitor extends NoPolicyVisitor
{
    /**
     * @var BuildResult|null
     */
    private $buildResult;

    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var AbstractTask
     */
    private $task;

    /**
     * DeployPolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, ?BuildResult $buildResult, AbstractTask $task)
    {
        parent::__construct($io, $task);

        $this->buildResult = $buildResult;
        $this->io = $io;
        $this->task = $task;
    }

    public function validate()
    {
        if (null === $this->buildResult) {
            $this->io->error('To '.$this->task->describe().' a successful build it required.');

            return false;
        }

        return parent::validate();
    }
}
