<?php

namespace Agnes\Models\Executor;

abstract class Executor
{
    public function copyRecursive(string $source, string $destination): string
    {
        return "cp -r $source $destination";
    }

    public function removeRecursive(string $folder): string
    {
        return "rm -rf $folder";
    }

    public function createSymbolicLink(string $filePath, string $destination): string
    {
        return "ln -s $destination $filePath";
    }

    public function readSymbolicLink(string $filePath): string
    {
        return "readlink -f $filePath";
    }

    public function uncompressTarGz(string $archivePath, string $targetFolder): string
    {
        return "tar -xzf $archivePath -C $targetFolder";
    }

    public function gitClone(string $path, string $repository): string
    {
        return 'git clone '.$repository." $path";
    }

    public function gitCheckout(string $path, string $commitish): string
    {
        return "git --git-dir=$path/.git  --work-tree=$path checkout ".$commitish;
    }

    /**
     * @return string
     */
    public function gitShowHash(string $path)
    {
        return "git --git-dir=$path/.git  --work-tree=$path show -s --format=%H";
    }

    public function makeDirRecursive(string $folder): string
    {
        return "mkdir -m 0777 -p $folder";
    }

    public function compressTarGz(string $folder, string $fileName): string
    {
        return "touch $folder/$fileName && tar -czvf $folder/$fileName --exclude=$fileName -C $folder .";
    }

    public function testFolderExists(string $folderPath, string $outputIfTrue): string
    {
        return $this->testFor("-d $folderPath", $outputIfTrue);
    }

    public function testFileExists(string $filePath, string $outputIfTrue): string
    {
        return $this->testFor("-f $filePath", $outputIfTrue);
    }

    public function testSymlinkExists(string $symlinkPath, string $outputIfTrue): string
    {
        return $this->testFor("-L $symlinkPath", $outputIfTrue);
    }

    private function testFor(string $testArgs, string $outputIfTrue): string
    {
        return "test $testArgs && echo \"$outputIfTrue\"";
    }

    public function listFolders(string $dir): string
    {
        return "ls -1d $dir/*";
    }

    public function rsync(string $source, string $target): string
    {
        return "rsync -chavzP $source $target";
    }

    public function sshCommand(string $destination, string $command): string
    {
        return 'ssh '.$destination." '$command'";
    }

    /**
     * @return string
     */
    public function executeWithinWorkingFolder(string $workingFolder, string $command)
    {
        return "cd $workingFolder && $command";
    }

    /**
     * @return string
     */
    public function setPermissions(string $filePath, int $permissions)
    {
        return "chmod $permissions $filePath";
    }

    /**
     * @return string
     */
    public function moveAndReplace(string $source, string $target)
    {
        return "rm -rf $target && mv -f $source $target";
    }

    abstract public function replaceSymlink(string $source, string $target): string;

    /**
     * @return string
     */
    public function convertToAbsolutePath(string $relativePath)
    {
        return "realpath $relativePath";
    }
}
