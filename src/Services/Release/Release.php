<?php


namespace Agnes\Services\Release;


class Release
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
     * @return string
     */
    public function getArchiveName(): string
    {
        return "release-" . $this->getName() . ".tar.gz";
    }
}
