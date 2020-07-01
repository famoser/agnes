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
    public function __construct(string $commitish, string $name, string $content)
    {
        parent::__construct($commitish, $name);

        $this->content = $content;
    }

    /**
     * @return self
     */
    public static function fromRelease(Release $release, string $content)
    {
        return new self($release->getCommitish(), $release->getName(), $content);
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
