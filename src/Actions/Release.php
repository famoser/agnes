<?php

namespace Agnes\Actions;

use Agnes\Services\PolicyService;
use Exception;

class Release extends AbstractPayload
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string
     */
    private $commitish;

    /**
     * Release constructor.
     *
     * @param string $name
     */
    public function __construct(string $commitish, string $name = null)
    {
        $this->commitish = $commitish;
        $this->name = $name;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function getName(): string
    {
        return null !== $this->name ? $this->name : $this->commitish;
    }

    /**
     * @throws Exception
     */
    public function canExecute(PolicyService $policyService): bool
    {
        return $policyService->canRelease($this);
    }

    public function describe(): string
    {
        return 'build '.$this->getCommitish();
    }
}
