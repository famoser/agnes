<?php


namespace Agnes\Actions;


use Agnes\Models\Instance;
use Agnes\Services\Github\ReleaseWithAsset;
use Agnes\Services\PolicyService;
use Exception;

class Deploy extends AbstractPayload
{
    /**
     * @var Instance
     */
    private $target;

    /**
     * @var Release
     */
    private $release;

    /**
     * @var string[]
     */
    private $filePaths;

    /**
     * Deployment constructor.
     * @param Release $release
     * @param Instance $target
     * @param string[] $files
     */
    public function __construct(Release $release, Instance $target, array $files)
    {
        $this->target = $target;
        $this->release = $release;
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
     * @return Release
     */
    public function getRelease(): Release
    {
        return $this->release;
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
        return "deploy " . $this->getRelease()->getName() . " to " . $this->getTarget()->describe();
    }
}
