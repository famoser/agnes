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

    public static function createFromInstanceSpecification(string $instanceSpecification): Filter
    {
        $entries = explode(':', $instanceSpecification);

        $parseToArray = function ($entry) {
            return '*' !== $entry ? explode(',', $entry) : null;
        };

        $entryCount = count($entries);
        $servers = $entryCount > 0 ? $parseToArray($entries[0]) : null;
        $environments = $entryCount > 1 ? $parseToArray($entries[1]) : null;
        $stages = $entryCount > 2 ? $parseToArray($entries[2]) : null;

        return new self($servers, $environments, $stages);
    }

    public static function createFromInstanceWithOverrideInstanceSpecification(Instance $instance, string $overrideInstanceSpecification): Filter
    {
        $server = $instance->getServerName();
        $environment = $instance->getEnvironmentName();
        $stage = $instance->getStage();

        $entries = explode(':', $overrideInstanceSpecification);

        $override = function (string $entry, string $default) {
            return '*' !== $entry ? $entry : $default;
        };
        $newSpecification = $override($entries[0], $server).':'.
            $override($entries[1], $environment).':'.
            $override($entries[2], $stage);

        return self::createFromInstanceSpecification($newSpecification);
    }

    public function instanceMatches(Instance $installation): bool
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

    public function describe(): string
    {
        $serverFilter = null !== $this->servers ? implode(',', $this->servers) : '*';
        $environementFilter = null !== $this->environments ? implode(',', $this->environments) : '*';
        $stageFilter = null !== $this->stages ? implode(',', $this->stages) : '*';

        return "$serverFilter:$environementFilter:$stageFilter";
    }
}
