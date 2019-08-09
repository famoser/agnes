<?php


namespace Agnes\Services;

use Agnes\Services\Release\Release;
use Agnes\Services\Github\Client;
use Agnes\Services\Github\ReleaseWithAsset;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

class GithubService
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * GithubService constructor.
     * @param HttpClient $httpClient
     * @param ConfigurationService $configurationService
     */
    public function __construct(HttpClient $httpClient, ConfigurationService $configurationService)
    {
        $this->httpClient = $httpClient;
        $this->configurationService = $configurationService;
    }

    /**
     * @var Client
     */
    private $clientCache;

    /**
     * @return Client
     * @throws \Exception
     */
    private function getClient()
    {
        if ($this->clientCache === null) {
            $config = $this->configurationService->getGithubConfig();
            $this->clientCache = new Client($this->httpClient, $config);
        }

        return $this->clientCache;
    }

    /**
     * @return ReleaseWithAsset[]
     * @throws Exception
     * @throws \Exception
     */
    public function releases()
    {
        $response = $this->getClient()->getReleases();
        $releases = json_decode($response->getBody()->getContents());

        $parsedRelease = [];
        foreach ($releases as $release) {
            $name = $release->name;
            $commitish = $release->target_commitish;
            if (count($release->assets) > 0) {
                $assetId = $release->assets[0]->id;
                $assetName = $release->assets[0]->name;

                $parsedRelease[] = new ReleaseWithAsset($name, $commitish, $assetId, $assetName);
            }
        }

        return $parsedRelease;
    }

    /**
     * @param string $assetId
     * @return string
     * @throws Exception
     * @throws \Exception
     */
    public function asset(string $assetId)
    {
        $response = $this->getClient()->downloadAsset($assetId);

        return $response->getBody()->getContents();
    }

    /**
     * @param Release $release
     * @param string $assetName
     * @param string $assetContentType
     * @param string $assetContent
     * @throws Exception
     * @throws \Exception
     */
    public function publish(Release $release, string $assetName, string $assetContentType, string $assetContent)
    {
        $response = $this->createRelease($release);

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = (int)$responseObject->id;

        $this->getClient()->addReleaseAsset($releaseId, $assetName, $assetContentType, $assetContent);
    }

    /**
     * @param Release $release
     * @return ResponseInterface
     * @throws Exception
     * @throws \Exception
     */
    private function createRelease(Release $release): ResponseInterface
    {
        $body = '
        {
          "tag_name": "' . $release->getName() . '",
          "target_commitish": "' . $release->getCommitish() . '",
          "name": "' . $release->getName() . '",
          "body": "Release of ' . $release->getName() . '",
          "draft": false,
          "prerelease": ' . $this->booleanToString(strpos($release->getName(), "-") > 0) . '
        }';

        return $this->getClient()->createRelease($body);
    }

    /**
     * @param bool $input
     * @return string
     */
    private function booleanToString(bool $input)
    {
        return $input ? "true" : "false";
    }
}
