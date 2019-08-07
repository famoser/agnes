<?php


namespace Agnes\Services;


use Agnes\Models\Connections\SSHConnection;

class FileService
{
    /**
     * @param string $filePath
     * @return string
     */
    public function readFileLocal(string $filePath): string
    {
        return file_get_contents($filePath);
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    public function writeFileLocal(string $filePath, string $content)
    {
        file_put_contents($filePath, $content);
    }

    /**
     * @param SSHConnection $SSHConnection
     * @param string $filePath
     * @return string
     */
    public function readFileSSH(SSHConnection $SSHConnection, string $filePath): string
    {
        $tempFile = $this->getTempFile();

        // download file
        $source = $SSHConnection->getDestination() . ":" . $SSHConnection->getWorkingFolder() . DIRECTORY_SEPARATOR . $filePath;
        exec("rsync -chavzP $source $tempFile");

        $content = file_get_contents($filePath);
        unlink($tempFile);

        return $content;
    }

    /**
     * @param SSHConnection $SSHConnection
     * @param string $filePath
     * @param string $content
     */
    public function writeFileSSH(SSHConnection $SSHConnection, string $filePath, string $content)
    {
        $tempFile = $this->getTempFile();
        file_put_contents($tempFile, $content);

        // download file
        $destination = $this->getRsyncPath($SSHConnection, $filePath);
        exec("rsync -chavzP $tempFile $destination");
    }

    /**
     * @param string $dir
     * @return string[]
     */
    public function getFoldersLocal(string $dir)
    {
        return glob("$dir/*", GLOB_ONLYDIR);
    }

    /**
     * @param SSHConnection $param
     * @param string $dir
     * @return array
     */
    public function getFoldersSSH(SSHConnection $param, string $dir)
    {
        $command = "ssh " . $param->getDestination() . " 'cd $dir && ls -1d */'";
        exec($command, $content);

        $dirs = [];
        foreach (explode("\n", $content) as $line) {
            // cut off last entry because it is /
            $dirs[] = substr($line, 0, -1);
        }

        return $dirs;
    }

    /**
     * @return string
     */
    private function getTempFile()
    {
        return tempnam(sys_get_temp_dir(), 'Agnes');
    }

    /**
     * @param SSHConnection $SSHConnection
     * @param string $filePath
     * @return string
     */
    private function getRsyncPath(SSHConnection $SSHConnection, string $filePath)
    {
        return $SSHConnection->getDestination() . ":" . $SSHConnection->getWorkingFolder() . DIRECTORY_SEPARATOR . $filePath;
    }
}