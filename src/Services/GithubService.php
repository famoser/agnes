<?php

namespace Agnes\Services;

use Agnes\Services\Github\Client;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Style\OutputStyle;

class GithubService
{
    /**
     * @var OutputStyle
     */
    private $io;

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
    public function __construct(OutputStyle $io, HttpClient $httpClient, ConfigurationService $configurationService)
    {
        $this->io = $io;
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
    public function commitishOfReleaseByReleaseName(string $releaseName): ?string
    {
        $response = $this->getClient()->getReleases();
        $releases = json_decode($response->getBody()->getContents());

        foreach ($releases as $release) {
            if ($release->name !== $releaseName) {
                continue;
            }

            return $release->target_commitish;
        }

        return null;
    }

    /**
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function downloadAssetForReleaseByReleaseName(string $releaseName)
    {
        $response = $this->getClient()->getReleases();
        $releases = json_decode($response->getBody()->getContents());

        foreach ($releases as $release) {
            if ($release->name !== $releaseName) {
                continue;
            }

            if (0 === count($release->assets)) {
                $this->io->error('Release '.$releaseName.' has no release asset.');

                return null;
            }

            $response = $this->getClient()->downloadAsset($release->assets[0]->id);

            return $response->getBody()->getContents();
        }

        $this->io->error('Release '.$releaseName.' does not exist.');

        return null;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function publish(string $name, string $commitish, string $content)
    {
        $response = $this->createRelease($name, $commitish);

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = (int) $responseObject->id;
        $assetName = $name.'.tar.gz';

        $this->getClient()->addReleaseAsset($releaseId, $assetName, 'application/zip', $content);
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
