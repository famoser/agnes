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
     * DeployPolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, ?BuildResult $buildResult, AbstractTask $task)
    {
        parent::__construct($io, $task);

        $this->buildResult = $buildResult;
    }

    public function validate()
    {
        if (null === $this->buildResult) {
            return $this->preventExecution('To execute this task, a successful build it required.');
        }

        return parent::validate();
    }
}
