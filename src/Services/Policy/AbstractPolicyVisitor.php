<?php

namespace Agnes\Services\Policy;

use Agnes\Models\Filter;
use Agnes\Models\Policy\Policy;
use Agnes\Models\Policy\SameReleasePolicy;
use Agnes\Models\Policy\StageWriteDownPolicy;
use Agnes\Models\Policy\StageWriteUpPolicy;
use Agnes\Models\Task\AbstractTask;
use Symfony\Component\Console\Style\StyleInterface;

abstract class AbstractPolicyVisitor
{
    public function __construct(private StyleInterface $io, private AbstractTask $task)
    {
    }

    public function visitStageWriteUp(StageWriteUpPolicy $stageWriteUpPolicy): bool
    {
        if (!$this->filterMatches($stageWriteUpPolicy->getFilter())) {
            return true;
        }

        return $this->checkStageWriteUp($stageWriteUpPolicy);
    }

    public function visitStageWriteDown(StageWriteDownPolicy $stageWriteDownPolicy): bool
    {
        if (!$this->filterMatches($stageWriteDownPolicy->getFilter())) {
            return true;
        }

        return $this->checkStageWriteDown($stageWriteDownPolicy);
    }

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
        return $this->policyPreventsExecution($policy, 'policy ' . get_class($policy) . ' has not been implemented for the executing task.');
    }

    protected function policyPreventsExecution(Policy $policy, string $reason): bool
    {
        $this->io->error('Policy ' . $policy->getName() . ' prevents execution of ' . $this->task->describe() . ': ' . $reason);

        return false;
    }

    protected function filterMatches(?Filter $filter): bool
    {
        if (null === $filter) {
            return true;
        }

        throw new \Exception('Filter is non-null; undecidable');
    }

    public function validate(): bool
    {
        return true;
    }
}
