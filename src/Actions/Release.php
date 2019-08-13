<?php


namespace Agnes\Actions;


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
     * @var string
     */
    private $body;

    /**
     * Release constructor.
     * @param string $name
     * @param string $commitish
     * @param string|null $body
     */
    public function __construct(string $name, string $commitish, ?string $body = null)
    {
        $this->commitish = $commitish;
        $this->name = $name;
        $this->body = $body === null ? 'Release of ' . $name : $body;
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
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $ending
     * @return string
     */
    public function getArchiveName(string $ending): string
    {
        return "release-" . $this->getName() . $ending;
    }
}
