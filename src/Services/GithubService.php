<?php

namespace Agnes\Services;

use Agnes\Models\Build;
use Agnes\Models\Setup;
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
     * @throws Exception
     * @throws \Exception
     */
    public function createSetupByReleaseName(string $releaseName): ?Setup
    {
        $response = $this->getClient()->getReleases();
        $releases = json_decode($response->getBody()->getContents());

        foreach ($releases as $release) {
            if ($release->name !== $releaseName || 0 === count($release->assets)) {
                continue;
            }

            $response = $this->getClient()->downloadAsset($release->assets[0]->id);
            $content = $response->getBody()->getContents();

            return Setup::fromRelease($releaseName, $release->target_commitish, $content);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function publish(string $name, Build $build)
    {
        $response = $this->createRelease($name, $build->getCommitish());

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = (int) $responseObject->id;
        $assetName = $name.'.tar.gz';

        $this->getClient()->addReleaseAsset($releaseId, $assetName, 'application/zip', $build->getContent());
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    private function createRelease(string $name, string $commitish): ResponseInterface
    {
        $isPrerelease = strpos($name, '-') > 0; // matches v1.0.0-alpha3

        $body = '
        {
          "tag_name": "'.$name.'",
          "target_commitish": "'.$commitish.'",
          "name": "'.$name.'",
          "body": "Release of '.$name.'",
          "draft": false,
          "prerelease": '.$this->booleanToString($isPrerelease).'
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
