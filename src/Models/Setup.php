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

    private function __construct(string $commitish)
    {
        $this->commitish = $commitish;
    }

    public static function fromRelease(string $releaseName, string $commitish, string $content)
    {
        $setup = new Setup($commitish);
        $setup->releaseName = $releaseName;
        $setup->content = $content;

        return $setup;
    }

    public static function fromBuild(Build $build, string $commitish)
    {
        $setup = new Setup($commitish);
        $setup->hash = $build->getHash();
        $setup->content = $build->getContent();

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

    public function toArray(): array
    {
        $array = ['commitish' => $this->commitish];
        if (null !== $this->hash) {
            $array['hash'] = $this->hash;
        }
        if (null !== $this->releaseName) {
            $array['release_name'] = $this->releaseName;
        }

        return $array;
    }

    public static function fromArray(array $array): self
    {
        $self = new self($array['commitish']);
        if (isset($array['hash'])) {
            $self->hash = $array['hash'];
        }
        if (isset($array['release_name'])) {
            $self->releaseName = $array['release_name'];
        }

        return $self;
    }
}
