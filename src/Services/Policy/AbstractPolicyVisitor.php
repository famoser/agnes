<?php

namespace Agnes\Services\Policy;

use Agnes\Models\Filter;
use Agnes\Models\Policy\Policy;
use Agnes\Models\Policy\SameReleasePolicy;
use Agnes\Models\Policy\StageWriteDownPolicy;
use Agnes\Models\Policy\StageWriteUpPolicy;
use Agnes\Models\Task\AbstractTask;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

abstract class AbstractPolicyVisitor
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var AbstractTask
     */
    private $task;

    /**
     * AbstractPolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, AbstractTask $task)
    {
        $this->io = $io;
        $this->task = $task;
    }

    /**
     * @throws Exception
     */
    public function visitStageWriteUp(StageWriteUpPolicy $stageWriteUpPolicy): bool
    {
        if (!$this->filterMatches($stageWriteUpPolicy->getFilter())) {
            return true;
        }

        return $this->checkStageWriteUp($stageWriteUpPolicy);
    }

    /**
     * @throws Exception
     */
    public function visitStageWriteDown(StageWriteDownPolicy $stageWriteDownPolicy): bool
    {
        if (!$this->filterMatches($stageWriteDownPolicy->getFilter())) {
            return true;
        }

        return $this->checkStageWriteDown($stageWriteDownPolicy);
    }

    /**
     * @throws Exception
     */
    public function visitSameRelease(SameReleasePolicy $sameReleasePolicy): bool
    {
        if (!$this->filterMatches($sameReleasePolicy->getFilter())) {
            return true;
        }

        return $this->checkSameRelease($sameReleasePolicy);
    }

    protected function checkStageWriteUp(StageWriteUpPolicy $policy): bool
    {
        return $this->checkDefault($policy);
    }

    protected function checkStageWriteDown(StageWriteDownPolicy $policy): bool
    {
        return $this->checkDefault($policy);
    }

    protected function checkSameRelease(SameReleasePolicy $policy): bool
    {
        return $this->checkDefault($policy);
    }

    protected function checkDefault(Policy $policy): bool
    {
        return $this->policyPreventsExecution($policy, 'policy '.get_class($policy).' has not been implemented for the executing task.');
    }

    protected function policyPreventsExecution(Policy $policy, string $reason): bool
    {
        $this->io->error('Policy '.$policy->getName().' prevents execution of '.$this->task->describe().': '.$reason);

        return false;
    }

    /**
     * @throws Exception
     */
    protected function filterMatches(?Filter $filter): bool
    {
        if (null === $filter) {
            return true;
        }

        throw new Exception('Filter is non-null; undecidable');
    }

    public function validate(): bool
    {
        return true;
    }
}
