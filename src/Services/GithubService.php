<?php

namespace Agnes\Services;

use Agnes\Actions\Release;
use Agnes\Models\Build;
use Agnes\Services\Github\Client;
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
     *
     * @throws \Exception
     */
    private function getClient()
    {
        if (null === $this->clientCache) {
            $config = $this->configurationService->getGithubConfig();
            $this->clientCache = new Client($this->httpClient, $config);
        }

        return $this->clientCache;
    }

    /**
     * @return Build|null
     *
     * @throws Exception
     */
    public function findBuild(string $releaseName)
    {
        $response = $this->getClient()->getReleases();
        $releases = json_decode($response->getBody()->getContents());

        foreach ($releases as $release) {
            if ($release->name !== $releaseName || 0 === count($release->assets)) {
                continue;
            }

            $response = $this->getClient()->downloadAsset($release->assets[0]->id);
            $content = $response->getBody()->getContents();

            return new Build($release->name, $release->target_commitish, $content);
        }

        return null;
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws \Exception
     */
    public function asset(string $assetId)
    {
    }

    /**
     * @throws Exception
     */
    public function publish(Build $build)
    {
        $response = $this->createRelease($build);

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = (int) $responseObject->id;
        $assetName = $build->getArchiveName('.tar.gz');

        $this->getClient()->addReleaseAsset($releaseId, $assetName, 'application/zip', $build->getContent());
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    private function createRelease(Release $release): ResponseInterface
    {
        $body = '
        {
          "tag_name": "'.$release->getName().'",
          "target_commitish": "'.$release->getCommitish().'",
          "name": "'.$release->getName().'",
          "body": "Release of '.$release->getName().'",
          "draft": false,
          "prerelease": '.$this->booleanToString(strpos($release->getName(), '-') > 0).'
        }';

        return $this->getClient()->createRelease($body);
    }

    /**
     * @return string
     */
    private function booleanToString(bool $input)
    {
        return $input ? 'true' : 'false';
    }
}
