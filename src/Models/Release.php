<?php


namespace Agnes\Release;


class Release
{
    /**
     * @var string
     */
    private $targetCommitish;

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
        $this->targetCommitish = $targetCommitish;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTargetCommitish(): string
    {
        return $this->targetCommitish;
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
