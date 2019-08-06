<?php


namespace Agnes\Release;


use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class CompressionService
{
    /**
     * @param string $folder
     * @param string $zipFile
     * @return bool
     * @throws \Exception
     */
    public function compress(string $folder, string $zipFile)
    {
        $zip = new ZipArchive();

        if (!$zip->open($zipFile, ZipArchive::CREATE | ZIPARCHIVE::OVERWRITE)) {
            throw new \Exception("Failed to create archive");
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);

            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($folder . '/', '', $file . '/'));
            } else if (is_file($file) === true) {
                $zip->addFile($file, str_replace($folder . '/', '', $file));
            }
        }

        if (!$zip->status == ZIPARCHIVE::ER_OK) {
            throw new \Exception("Failed to write files to zip");
        }

        return $zip->close();
    }
}