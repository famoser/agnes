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
     */
    public function __construct(string $name, string $commitish, string $content)
    {
        parent::__construct($name, $commitish);

        $this->content = $content;
    }

    /**
     * @return self
     */
    public static function fromRelease(Release $release, string $content)
    {
        return new self($release->getName(), $release->getCommitish(), $content);
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
