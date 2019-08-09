<?php


namespace Agnes\Services\Github;


use Agnes\Services\Release\Release;

class ReleaseWithAsset extends Release
{
    /**
     * @var int
     */
    private $assetId;

    /**
     * @var string
     */
    private $assetName;

    /**
     * ReleaseWithAsset constructor.
     * @param string $name
     * @param string $commitish
     * @param int $assetId
     * @param string $assetName
     */
    public function __construct(string $name, string $commitish, int $assetId, string $assetName)
    {
        parent::__construct($name, $commitish);

        $this->assetId = $assetId;
        $this->assetName = $assetName;
    }

    /**
     * @return int
     */
    public function getAssetId(): int
    {
        return $this->assetId;
    }

    /**
     * @return string
     */
    public function getAssetName(): string
    {
        return $this->assetName;
    }
}