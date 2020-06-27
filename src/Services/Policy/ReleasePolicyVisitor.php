<?php

namespace Agnes\Services\Policy;

use Agnes\Actions\Release;
use Agnes\Models\Filter;
use Agnes\Models\Policies\ReleaseWhitelistPolicy;
use Symfony\Component\Console\Output\OutputInterface;

class ReleasePolicyVisitor extends PolicyVisitor
{
    /**
     * @var Release
     */
    private $release;

    /**
     * ReleasePolicyVisitor constructor.
     */
    public function __construct(OutputInterface $output, Release $release)
    {
        parent::__construct($output);

        $this->release = $release;
    }

    public function visitReleaseWhitelist(ReleaseWhitelistPolicy $releaseWhitelistPolicy): bool
    {
        if (!in_array($this->release->getCommitish(), $releaseWhitelistPolicy->getCommitishes())) {
            $this->preventExecution($this->release, $this->release->getCommitish().' not found in whitelist of '.implode(', ', $releaseWhitelistPolicy->getCommitishes()));

            return false;
        }

        return true;
    }

    /**
     * checks if the policy has to be checked for.
     *
     * @param Filter $filter
     *
     * @return bool
     */
    protected function filterApplies(?Filter $filter)
    {
        return true;
    }
}
