<?php


namespace Agnes\Models\Connections;


use Agnes\Models\Executors\Executor;
use Exception;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function substr;
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
     * @param Executor $executor
     * @param string $destination
     */
    public function __construct(Executor $executor, string $destination)
    {
        parent::__construct($executor);

        $this->destination = $destination;
        $this->executor = $executor;
    }

    /**
     * @param string $command
     * @return string
     * @throws Exception
     */
    public function executeCommand(string $command): string
    {
        $command = $this->executor->sshCommand($this->getDestination(), $command);

        return parent::executeCommand($command);
    }

    /**
     * @param string $workingFolder
     * @param string[] $commands
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

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @param string $filePath
     * @return string
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
     * @param string $filePath
     * @param string $content
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
    }

    /**
     * @param string $dir
     * @return string[]
     * @throws Exception
     */
    public function getFolders(string $dir): array
    {
        try {
            $command = $this->executor->listFolders($dir);
            $response = $this->executeCommand($command);
        } catch (Exception $exception) {
            if (strpos($exception->getMessage(), "No such file or directory") !== false) {
                return [];
            }

            throw $exception;
        }

        return explode("\n", $response);
    }

    /**
     * @param string $filePath
     * @return bool
     * @throws Exception
     */
    public function checkFileExists(string $filePath): bool
    {
        $command = $this->executor->testFileExists($filePath, "yes");
        return $this->testForOutput($command, "yes");
    }

    /**
     * @param string $folderPath
     * @return bool
     * @throws Exception
     */
    public function checkFolderExists(string $folderPath): bool
    {
        $command = $this->executor->testFolderExists($folderPath, "yes");
        return $this->testForOutput($command, "yes");
    }

    /**
     * @param string $command
     * @param string $expected
     * @return bool
     */
    private function testForOutput(string $command, string $expected)
    {
        try {
            $output = $this->executeCommand($command);
        } catch (Exception $exception) {
            return false;
        }

        return strpos($output, $expected) !== false;
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function getSSHRsyncPath(string $filePath)
    {
        return $this->getDestination() . ":" . $filePath;
    }

    /**
     * @return string
     */
    private static function getTempFile()
    {
        return tempnam(sys_get_temp_dir(), 'Agnes');
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    public function equals(Connection $connection): bool
    {
        return $connection instanceof SSHConnection && $connection->getDestination() === $this->getDestination();
    }
}