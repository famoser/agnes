<?php

/*
 * This file is part of the famoser/agnes project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Agnes\Services\Configuration;

use Agnes\Models\Connection\Connection;

class Server
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $keepInstallations;

    /**
     * @var Environment[]
     */
    private $environments;

    /**
     * Server constructor.
     *
     * @param Environment[] $environments
     */
    public function __construct(string $name, Connection $connection, string $path, int $keepInstallations, array $scriptOverrides, array $environments)
    {
        $this->name = $name;
        $this->connection = $connection;
        $this->path = $path;
        $this->keepInstallations = $keepInstallations;
        $this->environments = $environments;

        $connection->setScriptOverrides($scriptOverrides);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getKeepInstallations(): int
    {
        return $this->keepInstallations;
    }

    /**
     * @return Environment[]
     */
    public function getEnvironments(): array
    {
        return $this->environments;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
