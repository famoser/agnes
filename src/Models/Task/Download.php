<?php

namespace Agnes\Models\Task;

use Agnes\Services\Task\AbstractTaskVisitor;

class Download extends AbstractTask
{
    const NAME = 'download';

    /**
     * @var string
     */
    private $release;

    /**
     * @var string
     */
    private $assetId;

    /**
     * DownloadGithub constructor.
     */
    public function __construct(string $release, string $assetId)
    {
        $this->release = $release;
        $this->assetId = $assetId;
    }

    public function getRelease(): string
    {
        return $this->release;
    }

    public function getAssetId(): string
    {
        return $this->assetId;
    }

    public function accept(AbstractTaskVisitor $abstractActionVisitor)
    {
        return $abstractActionVisitor->visitDownload($this);
    }

    public function describe(): string
    {
        return 'download asset '.$this->assetId.' of release '.$this->release;
    }

    public function name(): string
    {
        return self::NAME;
    }
}
