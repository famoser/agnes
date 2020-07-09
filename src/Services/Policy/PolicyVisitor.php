<?php

namespace Agnes\Services\Policy;

use Agnes\Models\Filter;
use Agnes\Models\Policy\Policy;
use Agnes\Models\Policy\ReleaseWhitelistPolicy;
use Agnes\Models\Policy\SameReleasePolicy;
use Agnes\Models\Policy\StageWriteDownPolicy;
use Agnes\Models\Policy\StageWriteUpPolicy;
use Agnes\Models\Task\AbstractTask;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

abstract class PolicyVisitor
{
    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * PolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io)
    {
        $this->io = $io;
    }

    /**
     * @throws Exception
     */
    public function visitStageWriteUp(StageWriteUpPolicy $stageWriteUpPolicy): bool
    {
        return $this->visitDefault($stageWriteUpPolicy);
    }

    /**
     * @throws Exception
     */
    public function visitStageWriteDown(StageWriteDownPolicy $stageWriteDownPolicy): bool
    {
        return $this->visitDefault($stageWriteDownPolicy);
    }

    /**
     * @throws Exception
     */
    public function visitReleaseWhitelist(ReleaseWhitelistPolicy $releaseWhitelistPolicy): bool
    {
        return $this->visitDefault($releaseWhitelistPolicy);
    }

    /**
     * @throws Exception
     */
    public function visitSameRelease(SameReleasePolicy $sameReleasePolicy): bool
    {
        return $this->visitDefault($sameReleasePolicy);
    }

    public function isApplicable(Policy $policy): bool
    {
        return $this->filterApplies($policy->getFilter());
    }

    /**
     * checks if the policy has to be checked for.
     *
     * @param Filter $filter
     *
     * @return bool
     */
    abstract protected function filterApplies(?Filter $filter);

    /**
     * @throws Exception
     */
    private function visitDefault(Policy $stageWriteDownPolicy): bool
    {
        $this->io->error('The policy '.get_class($stageWriteDownPolicy).' has not been implemented for the executing task.');

        return false;
    }

    protected function preventExecution(AbstractTask $payload, string $reason)
    {
        $this->io->error('Cannot execute '.$payload->describe().': '.$reason);

        return false;
    }
}
