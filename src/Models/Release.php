<?php


namespace Agnes\Release;


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
     * @param string $targetCommitish
     */
    public function __construct(string $name, string $targetCommitish)
    {
        $this->commitish = $targetCommitish;
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
