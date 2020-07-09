<?php

namespace Agnes\Services\Policy;

use Agnes\Models\Filter;
use Agnes\Models\Policy\ReleaseWhitelistPolicy;
use Agnes\Models\Task\Release;
use Symfony\Component\Console\Style\StyleInterface;

class ReleasePolicyVisitor extends PolicyVisitor
{
    /**
     * @var Release
     */
    private $release;

    /**
     * ReleasePolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, Release $release)
    {
        parent::__construct($io);

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
