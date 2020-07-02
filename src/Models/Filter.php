<?php

namespace Agnes\Models;

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
     *
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
     * @return bool
     */
    public function instanceMatches(Instance $installation)
    {
        $serverName = $installation->getServerName();
        $environmentName = $installation->getEnvironmentName();
        $stage = $installation->getStage();

        if (null !== $this->servers && !in_array($serverName, $this->servers)) {
            return false;
        }

        if (null !== $this->environments && !in_array($environmentName, $this->environments)) {
            return false;
        }

        if (null !== $this->stages && !in_array($stage, $this->stages)) {
            return false;
        }

        return true;
    }
}
