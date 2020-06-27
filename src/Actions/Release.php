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
     * @param string $commitish
     * @param string $name
     */
    public function __construct(string $commitish, string $name = null)
    {
        $this->commitish = $commitish;
        $this->name = $name;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getCommitish(): string
    {
        return $this->commitish;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name !== null ? $this->name : $this->hash;
    }

    /**
     * @param string $ending
     * @return string
     */
    public function getArchiveName(string $ending): string
    {
        return "release-" . $this->getName() . $ending;
    }

    /**
     * @param PolicyService $policyService
     * @return bool
     * @throws Exception
     */
    public function canExecute(PolicyService $policyService): bool
    {
        return $policyService->canRelease($this);
    }

    /**
     * @return string
     */
    public function describe(): string
    {
        return "build " . $this->getCommitish();
    }
}
