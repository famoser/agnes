<?php


namespace Agnes\Actions;


use Agnes\Models\Build;
use Agnes\Models\Instance;
use Agnes\Services\PolicyService;
use Exception;

class Deploy extends AbstractPayload
{
    /**
     * @var Instance
     */
    private $target;

    /**
     * @var Build
     */
    private $build;

    /**
     * @var string[]
     */
    private $filePaths;

    /**
     * Deployment constructor.
     * @param Build $build
     * @param Instance $target
     * @param string[] $files
     */
    public function __construct(Build $build, Instance $target, array $files)
    {
        $this->target = $target;
        $this->build = $build;
        $this->filePaths = $files;
    }

    /**
     * @return Instance
     */
    public function getTarget(): Instance
    {
        return $this->target;
    }

    /**
     * @return Build
     */
    public function getBuild(): Build
    {
        return $this->build;
    }

    /**
     * @return string[]
     */
    public function getFilePaths(): array
    {
        return $this->filePaths;
    }

    /**
     * @param PolicyService $policyService
     * @return bool
     * @throws Exception
     */
    public function canExecute(PolicyService $policyService): bool
    {
        return $policyService->canDeploy($this);
    }

    /**
     * @return string
     */
    public function describe(): string
    {
        return "deploy " . $this->getBuild()->getName() . " to " . $this->getTarget()->describe();
    }
}
