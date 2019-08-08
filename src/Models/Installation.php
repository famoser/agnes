<?php


namespace Agnes\Models;

use Agnes\Release\Release;

class Installation
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var ?\DateTime
     */
    private $installationAt;

    /**
     * @var Release?
     */
    private $release;

    /**
     * Installation constructor.
     * @param string $path
     * @param \DateTime|null $installationAt
     * @param Release|null $release
     */
    public function __construct(string $path, ?Release $release = null, ?\DateTime $installationAt = null)
    {
        $this->path = $path;
        $this->installationAt = $installationAt;
        $this->release = $release;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return \DateTime|null
     */
    public function getInstallationAt(): ?\DateTime
    {
        return $this->installationAt;
    }

    /**
     * @return Release|null ?Release
     */
    public function getRelease(): ?Release
    {
        return $this->release;
    }
}