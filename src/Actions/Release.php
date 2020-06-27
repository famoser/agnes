<?php

namespace Agnes\Actions;

use Agnes\Services\PolicyService;
use Exception;

class Release extends AbstractPayload
{
    /**
     * @var string
     */
    private $commitish;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string
     */
    private $hash;

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

    public function setHash(string $hash)
    {
        $this->hash = $hash;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function getName(): string
    {
        return null !== $this->name ? $this->name : $this->hash;
    }

    public function getArchiveName(string $ending): string
    {
        return 'release-'.$this->getName().$ending;
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
