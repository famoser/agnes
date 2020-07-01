<?php

namespace Agnes\Models;

class Setup
{
    /**
     * @var string
     */
    private $commitish;

    /**
     * @var string|null
     */
    private $releaseName;

    /**
     * @var string|null
     */
    private $hash;

    /**
     * @var string
     */
    private $content;

    private function __construct(string $commitish, string $content)
    {
        $this->commitish = $commitish;
        $this->content = $content;
    }

    public static function fromRelease(string $releaseName, string $commitish, string $content)
    {
        $setup = new Setup($commitish, $content);
        $setup->releaseName = $releaseName;

        return $setup;
    }

    public static function fromBuild(Build $build, string $commitish)
    {
        $setup = new Setup($commitish, $build->getContent());
        $setup->hash = $build->getHash();

        return $setup;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function getReleaseName(): ?string
    {
        return $this->releaseName;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getIdentification(): string
    {
        return null !== $this->getReleaseName() ? $this->getReleaseName() : $this->getHash();
    }
}
