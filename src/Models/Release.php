<?php


namespace Agnes\Release;


class Release
{
    /**
     * @var string
     */
    private $tagName;

    /**
     * @var string
     */
    private $targetCommitish;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $prerelease;

    /**
     * @var string
     */
    private $draft;

    /**
     * @var string
     */
    private $assetName;

    /**
     * @var string
     */
    private $assetContentType = "application/zip";

    /**
     * @var string
     */
    private $assetContent;

    /**
     * Release constructor.
     * @param string $name
     * @param string $targetCommitish
     * @param string|null $description
     */
    public function __construct(string $name, string $targetCommitish, string $description = null)
    {
        $this->tagName = $name;
        $this->targetCommitish = $targetCommitish;
        $this->name = $name;
        $this->description = $description ?? "Release of " . $name;
        $this->prerelease = strpos($name, "-") > 0;
        $this->draft = false;
    }

    /**
     * @param string $assetName
     * @param string $assetContentType
     * @param string $assetContent
     */
    public function setAsset(string $assetName, string $assetContentType, string $assetContent)
    {
        $this->assetName = $assetName;
        $this->assetContentType = $assetContentType;
        $this->assetContent = $assetContent;
    }

    /**
     * @return string
     */
    public function getTagName(): string
    {
        return $this->tagName;
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
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getDraft(): string
    {
        return $this->draft;
    }

    /**
     * @return string
     */
    public function getPrerelease(): string
    {
        return $this->prerelease;
    }

    /**
     * @return string
     */
    public function getAssetName(): string
    {
        return $this->assetName;
    }

    /**
     * @return string
     */
    public function getAssetContentType(): string
    {
        return $this->assetContentType;
    }

    /**
     * @return string
     */
    public function getAssetContent(): string
    {
        return $this->assetContent;
    }
}
