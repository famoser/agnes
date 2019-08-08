<?php


namespace Agnes\Models\Connections;


use Agnes\Models\Tasks\Task;
use Agnes\Services\TaskService;
use function exec;
use function exec as exec1;
use function exec as exec2;
use function exec as exec3;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function strlen;
use function substr;
use function unlink;
use const DIRECTORY_SEPARATOR;

class SSHConnection extends Connection
{
    /**
     * @var string
     */
    private $destination;

    /**
     * SSHConnection constructor.
     * @param string $workingFolder
     * @param string $destination
     */
    public function __construct(string $workingFolder, string $destination)
    {
        parent::__construct($workingFolder);

        $this->destination = $destination;
    }

    /**
     * @param Task $task
     * @param TaskService $service
     * @throws \Exception
     */
    public function executeTask(Task $task, TaskService $service)
    {
        $service->executeSSH($this, $task);
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
     */
    public function readFile(string $filePath): string
    {
        $tempFile = self::getTempFile();

        // download file
        $source = $this->getDestination() . ":" . $this->getWorkingFolder() . DIRECTORY_SEPARATOR . $filePath;
        exec1("rsync -chavzP $source $tempFile");

        $content = file_get_contents($filePath);
        unlink($tempFile);

        return $content;
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    public function writeFile(string $filePath, string $content)
    {
        $tempFile = self::getTempFile();
        file_put_contents($tempFile, $content);

        // download file
        $destination = self::getRsyncPath($this, $filePath);
        exec2("rsync -chavzP $tempFile $destination");
    }

    /**
     * @param string $dir
     * @return string[]
     */
    public function getFolders(string $dir): array
    {
        $command = "ssh " . $this->getDestination() . " 'cd $dir && ls -1d */'";
        exec3($command, $content);

        $dirs = [];
        foreach (explode("\n", $content) as $line) {
            // cut off last entry because it is /
            $dirs[] = substr($line, 0, -1);
        }

        return $dirs;
    }

    /**
     * @param string $filePath
     * @return bool
     */
    public function checkFileExists(string $filePath): bool
    {
        return $this->testFor("-f $filePath");
    }

    /**
     * @param string $folderPath
     * @return bool
     */
    public function checkFolderExists(string $folderPath): bool
    {
        return $this->testFor("-d $folderPath");
    }

    /**
     * @param string $testArgs
     * @return int
     */
    private function testFor(string $testArgs)
    {
        $command = "ssh " . $this->getDestination() . " 'test $testArgs && echo \"yes\"'";
        exec($command, $output);

        return strlen($output === 3);
    }

    /**
     * @param SSHConnection $SSHConnection
     * @param string $filePath
     * @return string
     */
    private static function getRsyncPath(SSHConnection $SSHConnection, string $filePath)
    {
        return $SSHConnection->getDestination() . ":" . $SSHConnection->getWorkingFolder() . DIRECTORY_SEPARATOR . $filePath;
    }

    /**
     * @return string
     */
    private static function getTempFile()
    {
        return tempnam(sys_get_temp_dir(), 'Agnes');
    }
}