<?php


namespace Agnes\Release;


use Agnes\Configuration\Github;
use GuzzleHttp\Psr7\Request;
use Http\Client\Exception;
use Http\Client\HttpClient;

class ReleaseService
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var Github
     */
    private $github;

    /**
     * ReleaseService constructor.
     * @param HttpClient $httpClient
     * @param Github $github
     */
    public function __construct(HttpClient $httpClient, Github $github)
    {
        $this->httpClient = $httpClient;
        $this->github = $github;
    }

    /**
     * @param Release $release
     * @throws Exception
     * @throws \Exception
     */
    public function publishRelease(Release $release)
    {
        $request = new Request(
            'POST',
            'https://api.github.com/repos/' . $this->github->getRepository() . '/releases',
            ["Authorization" => "token " . $this->github->getApiToken()],
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

        $responseJson = $response->getBody()->getContents();
        $responseObject = json_decode($responseJson);
        $releaseId = $responseObject->id;

        //application/zip

        $request = new Request(
            'POST',
            'https://api.github.com/repos/' . $this->github->getRepository() . '/releases/' . $releaseId . "/assets?name=" . $release->getAssetName(),
            [
                "Authorization" => "token " . $this->github->getApiToken(),
                "Content-Type" => $release->getAssetContentType()
            ],
            $release->getAssetName()
        );
        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 201) {
            throw new \Exception("Creation of release asset failed with status code " . $response->getStatusCode());
        }
    }
}
