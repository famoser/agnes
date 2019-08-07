<?php


namespace Agnes\Services\Github;


use Agnes\Release\Release;

class ReleaseWithAsset extends Release
{
    /**
     * @var int
     */
    private $assetId;

    /**
     * ReleaseWithAsset constructor.
     * @param int $assetId
     */
    public function __construct(string $name, string $commitish, int $assetId)
    {
        parent::__construct($name, $commitish);

        $this->assetId = $assetId;
    }

    /**
     * @return int
     */
    public function getAssetId(): int
    {
        return $this->assetId;
    }
}