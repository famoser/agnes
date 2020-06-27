<?php


namespace Agnes\Services;

use Agnes\Actions\Release;
use Agnes\Models\Build;
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
     * @param string $releaseName
     * @return Build|null
     * @throws Exception
     */
    public function findBuild(string $releaseName)
    {
        $response = $this->getClient()->getReleases();
        $releases = json_decode($response->getBody()->getContents());

        foreach ($releases as $release) {
            if ($release->name !== $releaseName || count($release->assets) === 0) {
                continue;
            }

            $response = $this->getClient()->downloadAsset($release->assets[0]->id);
            $content = $response->getBody()->getContents();

            return new Build($release->name, $release->target_commitish, $content);
        }

        return null;
    }

    /**
     * @param string $assetId
     * @return string
     * @throws Exception
     * @throws \Exception
     */
    public function asset(string $assetId)
    {
    }

    /**
     * @param Build $build
     * @throws Exception
     */
    public function publish(Build $build)
    {
        $response = $this->createRelease($build);

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = (int)$responseObject->id;
        $assetName = $build->getArchiveName(".tar.gz");

        $this->getClient()->addReleaseAsset($releaseId, $assetName, "application/zip", $build->getContent());
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
