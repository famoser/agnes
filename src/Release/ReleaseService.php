<?php


namespace Agnes\Release;

use Agnes\Services\Configuration\GithubConfig;
use GuzzleHttp\Psr7\Request;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

class ReleaseService
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * ReleaseService constructor.
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param Release $release
     * @param GithubConfig $githubConfig
     * @throws Exception
     */
    public function publishRelease(Release $release, GithubConfig $githubConfig)
    {
        $response = $this->createRelease($release, $githubConfig);

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = (int)$responseObject->id;

        $this->addReleaseAsset($releaseId, $release, $githubConfig);
    }

    /**
     * @param Release $release
     * @param GithubConfig $config
     * @return ResponseInterface
     * @throws Exception
     * @throws \Exception
     */
    private function createRelease(Release $release, GithubConfig $config): ResponseInterface
    {
        $request = new Request(
            'POST',
            'https://api.github.com/repos/' . $config->getRepository() . '/releases',
            ["Authorization" => "token " . $config->getApiToken()],
            '
            {
              "tag_name": "' . $release->getTagName() . '",
              "target_commitish": "' . $release->getTargetCommitish() . '",
              "name": "' . $release->getName() . '",
              "body": "' . $release->getDescription() . '",
              "draft": ' . $release->getDraft() . ',
              "prerelease": ' . $release->getPrerelease() . '
            }'
        );

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 201) {
            throw new \Exception("Creation of release failed with status code " . $response->getStatusCode());
        }

        return $response;
    }

    /**
     * @param int $releaseId
     * @param Release $release
     * @param GithubConfig $config
     * @return ResponseInterface
     * @throws Exception
     * @throws \Exception
     */
    private function addReleaseAsset(int $releaseId, Release $release, GithubConfig $config): ResponseInterface
    {
        $request = new Request(
            'POST',
            'https://api.github.com/repos/' . $config->getRepository() . '/releases/' . $releaseId . "/assets?name=" . $release->getAssetName(),
            [
                "Authorization" => "token " . $config->getApiToken(),
                "Content-Type" => $release->getAssetContentType()
            ],
            $release->getAssetName()
        );
        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 201) {
            throw new \Exception("Creation of release asset failed with status code " . $response->getStatusCode());
        }

        return $response;
    }
}
