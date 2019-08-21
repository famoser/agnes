<?php


namespace Agnes\Models\Executors;


abstract class Executor
{
    /**
     * @param string $source
     * @param string $destination
     * @return string
     */
    public function copyRecursive(string $source, string $destination): string
    {
        return "cp -r $source $destination";
    }

    /**
     * @param string $folder
     * @return string
     */
    public function removeRecursive(string $folder): string
    {
        return "rm -rf $folder";
    }

    /**
     * @param string $filePath
     * @param string $destination
     * @return string
     */
    public function createSymbolicLink(string $filePath, string $destination): string
    {
        return "ln -s $destination $filePath";
    }

    /**
     * @param string $archivePath
     * @param string $targetFolder
     * @return string
     */
    public function uncompressTarGz(string $archivePath, string $targetFolder): string
    {
        return "tar -xzf $archivePath -C $targetFolder";
    }

    /**
     * @param string $path
     * @param string $repository
     * @return string
     */
    public function gitClone(string $path, string $repository): string
    {
        return "git clone git@github.com:" . $repository . " $path";
    }

    /**
     * @param string $path
     * @param string $commitish
     * @return string
     */
    public function gitCheckout(string $path, string $commitish): string
    {
        return "git --git-dir=$path/.git  --work-tree=$path checkout " . $commitish;
    }

    /**
     * @param string $folder
     * @return string
     */
    public function makeDirRecursive(string $folder): string
    {
        return "mkdir -m 0777 -p $folder";
    }

    /**
     * @param string $folder
     * @param string $fileName
     * @return string
     */
    public function compressTarGz(string $folder, string $fileName): string
    {
        return "touch $folder/$fileName && tar -czvf $folder/$fileName --exclude=$fileName -C $folder .";
    }

    /**
     * @param string $folderPath
     * @param string $outputIfTrue
     * @return string
     */
    public function testFolderExists(string $folderPath, string $outputIfTrue): string
    {
        return $this->testFor("-d $folderPath", $outputIfTrue);
    }

    /**
     * @param string $folderPath
     * @param string $outputIfTrue
     * @return string
     */
    public function testFileExists(string $folderPath, string $outputIfTrue): string
    {
        return $this->testFor("-f $folderPath", $outputIfTrue);
    }

    /**
     * @param string $testArgs
     * @param string $outputIfTrue
     * @return string
     */
    private function testFor(string $testArgs, string $outputIfTrue): string
    {
        return "test $testArgs && echo \"$outputIfTrue\"";
    }

    /**
     * @param string $dir
     * @return string
     */
    public function listFolders(string $dir): string
    {
        return "ls -1d $dir/*";
    }

    /**
     * @param string $source
     * @param string $target
     * @return string
     */
    public function rsync(string $source, string $target): string
    {
        return "rsync -chavzP $source $target";
    }

    /**
     * @param string $destination
     * @param string $command
     * @return string
     */
    public function sshCommand(string $destination, string $command): string
    {
        return "ssh " . $destination . " '$command'";
    }

    /**
     * @param string $workingFolder
     * @param string $command
     * @return string
     */
    public function executeWithinWorkingFolder(string $workingFolder, string $command)
    {
        return "cd $workingFolder && $command";
    }

    /**
     * @param string $filePath
     * @param int $permissions
     * @return string
     */
    public function setPermissions(string $filePath, int $permissions)
    {
        return "chmod $permissions $filePath";
    }

    /**
     * @param string $source
     * @param string $target
     * @return string
     */
    public function moveAndReplace(string $source, string $target)
    {
        return "rm -rf $target && mv -f $source $target";
    }

    /**
     * @param string $source
     * @param string $target
     * @return string
     */
    public abstract function replaceSymlink(string $source, string $target): string;
}