<?php


namespace Agnes\Models;


use Agnes\Actions\Release;

class Build extends Release
{
    /**
     * @var string
     */
    private $content;

    /**
     * Build constructor.
     * @param string $name
     * @param string $commitish
     * @param string $content
     */
    public function __construct(string $name, string $commitish, string $content)
    {
        parent::__construct($name, $commitish);

        $this->content = $content;
    }

    /**
     * @param Release $release
     * @param string $content
     *
     * @return self
     */
    public static function fromRelease(Release $release, string $content)
    {
        return new self($release->getName(), $release->getCommitish(), $content);
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
}
