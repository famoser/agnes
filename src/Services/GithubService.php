<?php


namespace Agnes\Release;

use Agnes\Services\ConfigurationService;
use Agnes\Services\Github\Client;
use Agnes\Services\Github\ReleaseWithAsset;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

class GithubService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * ReleaseService constructor.
     * @param HttpClient $httpClient
     * @param ConfigurationService $configurationService
     * @throws \Exception
     */
    public function __construct(HttpClient $httpClient, ConfigurationService $configurationService)
    {
        $config = $configurationService->getGithubConfig();

        $this->client = new Client($httpClient, $config);
    }

    /**
     * @return ReleaseWithAsset[]
     * @throws Exception
     * @throws \Exception
     */
    public function releases()
    {
        $response = $this->client->getReleases();
        $releases = json_decode($response);

        $parsedRelease = [];
        foreach ($releases as $release) {
            $name = $release->name;
            $commitish = $release->target_commitish;
            if (count($release->assets) > 0) {
                $assetId = $release->assets[0]->id;

                $parsedRelease[] = new ReleaseWithAsset($name, $commitish, $assetId);
            }
        }

        return $parsedRelease;
    }

    /**
     * @param string $assetId
     * @return string
     * @throws Exception
     */
    public function asset(string $assetId)
    {
        $response = $this->client->downloadAsset($assetId);

        return $response->getBody()->getContents();
    }

    /**
     * @param Release $release
     * @param string $assetName
     * @param string $assetContentType
     * @param string $assetContent
     * @throws Exception
     */
    public function publish(Release $release, string $assetName, string $assetContentType, string $assetContent)
    {
        $response = $this->createRelease($release);

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = (int)$responseObject->id;

        $this->client->addReleaseAsset($releaseId, $assetName, $assetContentType, $assetContent);
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

        return $this->client->createRelease($body);
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
