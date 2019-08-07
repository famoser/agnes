<?php


namespace Agnes\Services\Instance;


use Agnes\Release\Release;

class Meta
{
    /**
     * @var \DateTime
     */
    private $installationDateTime;

    /**
     * @var Release
     */
    private $release;

    /**
     * Meta constructor.
     * @param \DateTime $installationDateTime
     * @param Release $release
     */
    public function __construct(\DateTime $installationDateTime, Release $release)
    {
        $this->installationDateTime = $installationDateTime;
        $this->release = $release;
    }

    /**
     * @return \DateTime
     */
    public function getInstallationDateTime(): \DateTime
    {
        return $this->installationDateTime;
    }

    /**
     * @return Release
     */
    public function getRelease(): Release
    {
        return $this->release;
    }
}