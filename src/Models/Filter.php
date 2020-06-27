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
        return $this->isMatch($installation->getServerName(), $installation->getEnvironmentName(), $installation->getStage());
    }

    /**
     * @return bool
     */
    public function isMatch(string $server, string $environment, string $stage)
    {
        if (null !== $this->servers && !in_array($server, $this->servers)) {
            return false;
        }

        if (null !== $this->environments && !in_array($environment, $this->environments)) {
            return false;
        }

        if (null !== $this->stages && !in_array($stage, $this->stages)) {
            return false;
        }

        return true;
    }
}
