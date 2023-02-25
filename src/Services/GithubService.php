<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services;

use Agnes\Services\Github\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Style\OutputStyle;

class GithubService
{
    /**
     * @var OutputStyle
     */
    private $io;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * GithubService constructor.
     */
    public function __construct(OutputStyle $io, ConfigurationService $configurationService)
    {
        $this->io = $io;
        $this->configurationService = $configurationService;
    }

    /**
     * @var Client
     */
    private $clientCache;

    /**
     * @throws \Exception
     */
    private function getClient(): Client
    {
        if (null === $this->clientCache) {
            $config = $this->configurationService->getGithubConfig();
            if (null === $config) {
                throw new \Exception('github not configured; can not create github client');
            }

            $this->clientCache = new Client($config);
        }

        return $this->clientCache;
    }

    /**
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
     * @throws \Exception
     */
    public function downloadAssetForReleaseByReleaseName(string $releaseName): ?string
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
     * @throws \Exception
     */
    public function publish(string $name, string $commitish, string $content): void
    {
        $response = $this->createRelease($name, $commitish);

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = (int) $responseObject->id;
        $assetName = $name.'.tar.gz';

        $this->getClient()->addReleaseAsset($releaseId, $assetName, 'application/zip', $content);
    }

    /**
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

    private function booleanToString(bool $input): string
    {
        return $input ? 'true' : 'false';
    }

    /**
     * @throws \Exception
     */
    public function configured(): bool
    {
        return null !== $this->configurationService->getGithubConfig();
    }
}
