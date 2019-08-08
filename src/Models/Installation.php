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
    private $installedAt;

    /**
     * @var ?\DateTime
     */
    private $releasedAt;

    /**
     * @var Release?
     */
    private $release;

    /**
     * Installation constructor.
     * @param string $path
     * @param Release|null $release
     * @param \DateTime|null $installedAt
     * @param \DateTime|null $releasedAt
     */
    public function __construct(string $path, ?Release $release = null, ?\DateTime $installedAt = null, ?\DateTime $releasedAt = null)
    {
        $this->path = $path;
        $this->release = $release;
        $this->installedAt = $installedAt;
        $this->releasedAt = $releasedAt;
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
    public function getInstalledAt(): ?\DateTime
    {
        return $this->installedAt;
    }

    /**
     * @return Release|null ?Release
     */
    public function getRelease(): ?Release
    {
        return $this->release;
    }

    /**
     * @return mixed
     */
    public function getReleasedAt()
    {
        return $this->releasedAt;
    }
}