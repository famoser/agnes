<?php

namespace Agnes\Models\Executor;

abstract class Executor
{
    public function cpRecursive(string $source, string $destination): string
    {
        return "cp -r $source $destination";
    }

    public function rmRecursive(string $folder): string
    {
        return "rm -rf $folder";
    }

    public function lnCreateSymbolicLink(string $filePath, string $destination): string
    {
        return "ln -s $destination $filePath";
    }

    public function readlinkCanonicalize(string $filePath): string
    {
        return "readlink -f $filePath";
    }

    public function tarCompress(string $folder, string $fileName): string
    {
        return "tar -czvf $folder/$fileName --exclude=$fileName -C $folder .";
    }

    public function tarUncompress(string $archivePath, string $targetFolder): string
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

    public function gitPull(string $path)
    {
        return "git  --git-dir=$path/.git  --work-tree=$path pull";
    }

    /**
     * @return string
     */
    public function gitShowHash(string $path)
    {
        return "git --git-dir=$path/.git  --work-tree=$path show -s --format=%H";
    }

    public function mkdirRecursive(string $folder): string
    {
        return "mkdir -m 0777 -p $folder";
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

    public function lsFolders(string $dir): string
    {
        return "ls -1d $dir/*";
    }

    public function scpCopy(string $source, string $destination): string
    {
        return "scp $source $destination";
    }

    public function sshExecute(string $destination, string $command): string
    {
        return 'ssh '.$destination." '$command'";
    }

    /**
     * @return string
     */
    public function cdToFolderAndExecute(string $folder, string $command)
    {
        return "cd $folder && $command";
    }

    /**
     * @return string
     */
    public function chmodSetPermissions(string $filePath, int $permissions)
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

    abstract public function mvSymlinkAtomicReplace(string $source, string $target): string;
}
