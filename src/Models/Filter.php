<?php


namespace Agnes\Models\Tasks;

class Filter
{
    /**
     * @var string[]|null
     */
    private $servers;

    /**
     * @var string[]|null
     */
    private $environments;

    /**
     * @var string[]|null
     */
    private $stages;

    /**
     * Filter constructor.
     * @param string[]|null $servers
     * @param string[]|null $environments
     * @param string[]|null $stages
     */
    public function __construct(?array $servers, ?array $environments, ?array $stages)
    {
        $this->servers = $servers;
        $this->environments = $environments;
        $this->stages = $stages;
    }

    /**
     * @param Instance $installation
     * @return bool
     */
    public function instanceMatches(Instance $installation)
    {
        return $this->isMatch($installation->getServerName(), $installation->getEnvironmentName(), $installation->getStage());
    }

    /**
     * @param string $server
     * @param string $environment
     * @param string $stage
     * @return bool
     */
    public function isMatch(string $server, string $environment, string $stage)
    {
        if ($this->servers !== null && in_array($server, $this->servers)) {
            return true;
        }

        if ($this->environments !== null && in_array($environment, $this->environments)) {
            return true;
        }

        if ($this->stages !== null && in_array($stage, $this->stages)) {
            return true;
        }

        return false;
    }

}