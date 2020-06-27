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
     * @var string
     */
    private $name;

    /**
     * Release constructor.
     * @param string $name
     * @param string $commitish
     * @param string|null $body
     */
    public function __construct(string $name, string $commitish)
    {
        $this->commitish = $commitish;
        $this->name = $name;
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
        return $this->name;
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
        return "build " . $this->getCommitish() . " and then publish it under the name " . $this->getName();
    }
}
