<?php

namespace Agnes\Models\Connection;

use Agnes\Models\Executor\Executor;
use Exception;
use function explode;
use function file_get_contents;
use function file_put_contents;
use Symfony\Component\Console\Style\StyleInterface;
use function unlink;

class SSHConnection extends Connection
{
    /**
     * @var string
     */
    private $destination;

    /**
     * @var Executor
     */
    private $executor;

    /**
     * SSHConnection constructor.
     */
    public function __construct(StyleInterface $io, Executor $executor, string $destination)
    {
        parent::__construct($io, $executor);

        $this->destination = $destination;
        $this->executor = $executor;
    }

    /**
     * @throws Exception
     */
    public function executeCommand(string $command): string
    {
        $command = $this->executor->sshCommand($this->getDestination(), $command);

        return parent::executeCommand($command);
    }

    /**
     * @param string[] $commands
     *
     * @throws Exception
     */
    protected function executeWithinWorkingFolder(string $workingFolder, array $commands)
    {
        // prepare commands for execution
        foreach ($commands as &$command) {
            $command = $this->executor->executeWithinWorkingFolder($workingFolder, $command);
        }

        // execute commands
        $this->executeCommands($commands);
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @throws Exception
     */
    public function readFile(string $filePath): string
    {
        $tempFile = self::getTempFile();

        // download file
        $source = $this->getSSHRsyncPath($filePath);
        $command = $this->executor->rsync($source, $tempFile);
        parent::executeCommand($command);

        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content;
    }

    /**
     * @throws Exception
     */
    public function writeFile(string $filePath, string $content)
    {
        $tempFile = self::getTempFile();
        file_put_contents($tempFile, $content);

        // download file
        $destination = $this->getSSHRsyncPath($filePath);
        $command = $this->executor->rsync($tempFile, $destination);
        parent::executeCommand($command);

        // set permissive permissions
        $command = $this->executor->setPermissions($filePath, 644);
        $this->executeCommand($command);
    }

    /**
     * @return string[]
     *
     * @throws Exception
     */
    public function getFolders(string $dir): array
    {
        try {
            $command = $this->executor->listFolders($dir);
            $response = $this->executeCommand($command);
        } catch (Exception $exception) {
            return [];
        }

        $fullPaths = explode("\n", $response);

        return $this->keepFolderOnly($fullPaths);
    }

    public function checkFileExists(string $filePath): bool
    {
        $command = $this->executor->testFileExists($filePath, 'yes');

        return $this->testForOutput($command, 'yes');
    }

    public function checkSymlinkExists(string $symlinkPath): bool
    {
        $command = $this->executor->testSymlinkExists($symlinkPath, 'yes');

        return $this->testForOutput($command, 'yes');
    }

    public function checkFolderExists(string $folderPath): bool
    {
        $command = $this->executor->testFolderExists($folderPath, 'yes');

        return $this->testForOutput($command, 'yes');
    }

    /**
     * @return bool
     */
    private function testForOutput(string $command, string $expected)
    {
        try {
            $output = $this->executeCommand($command);
        } catch (Exception $exception) {
            return false;
        }

        return false !== strpos($output, $expected);
    }

    /**
     * @return string
     */
    private function getSSHRsyncPath(string $filePath)
    {
        return $this->getDestination().':'.$filePath;
    }

    /**
     * @return string
     */
    private static function getTempFile()
    {
        return tempnam(sys_get_temp_dir(), 'Agnes');
    }

    public function equals(Connection $connection): bool
    {
        return $connection instanceof SSHConnection && $connection->getDestination() === $this->getDestination();
    }
}
