<?php

namespace Agnes\Services;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Symfony\Component\Console\Style\StyleInterface;

class FileService
{
    public const ENCRYPTED_FILE_EXTENSION = '.encrypted';
    public const ENCRYPTION_ALGORITHM = 'aes-256-gcm';
    public const ENCRYPTION_IV_LENGTH = 12;
    public const ENCRYPTION_TAG_LENGTH = 16;
    public const ENCRYPTION_KEY_HASH_ALGORITHM = 'sha256';

    public function __construct(private StyleInterface $io, private ConfigurationService $configurationService)
    {
    }

    public function encrypt(Instance $instance): ?bool
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();

        $key = $this->configurationService->getConfigEncryptionKey();
        foreach ($configuredFiles as $configuredFile) {
            $configuredFileKey = $configuredFile->getPath();
            $expectedFilePath = $instanceConfigFolder . DIRECTORY_SEPARATOR . $configuredFileKey;

            if (!$configuredFile->getIsEncrypted() || !file_exists($expectedFilePath)) {
                continue;
            }

            if (!$key) {
                $this->io->error('No encryption key configured.');
                return false;
            }

            if (!extension_loaded('openssl')) {
                $this->io->error('openssl extension is not installed.');
                return false;
            }

            $fileContent = file_get_contents($expectedFilePath);
            $iv = openssl_random_pseudo_bytes(self::ENCRYPTION_IV_LENGTH);
            $hashedKey = hash(self::ENCRYPTION_KEY_HASH_ALGORITHM, $key);
            $encrypted = openssl_encrypt($fileContent, self::ENCRYPTION_ALGORITHM, $hashedKey, OPENSSL_RAW_DATA, $iv, $tag, tag_length: self::ENCRYPTION_TAG_LENGTH);

            $encryptedFilePath = $expectedFilePath . self::ENCRYPTED_FILE_EXTENSION;
            file_put_contents($encryptedFilePath, self::ENCRYPTION_ALGORITHM . $iv . $tag . $encrypted);
        }

        return true;
    }

    public function decrypt(Instance $instance, bool $check): ?bool
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();
        $missingFiles = [];
        $checkFailedFiles = [];

        $key = $this->configurationService->getConfigEncryptionKey();
        foreach ($configuredFiles as $configuredFile) {
            $configuredFileKey = $configuredFile->getPath();
            $expectedFilePath = $instanceConfigFolder . DIRECTORY_SEPARATOR . $configuredFileKey;

            if (!$configuredFile->getIsEncrypted()) {
                continue;
            }

            $expectedEncryptedFilePath = $expectedFilePath . self::ENCRYPTED_FILE_EXTENSION;
            if (!file_exists($expectedEncryptedFilePath)) {
                if ($configuredFile->getIsRequired()) {
                    $missingFiles[$configuredFileKey] = $expectedEncryptedFilePath;
                }
                continue;
            }

            if (!$key) {
                $this->io->error('No encryption key configured.');
                return false;
            }

            if (!extension_loaded('openssl')) {
                $this->io->error('openssl extension is not installed.');
                return false;
            }

            $encryptedFileContent = file_get_contents($expectedEncryptedFilePath);
            $startOfPayload = strlen(self::ENCRYPTION_ALGORITHM) + self::ENCRYPTION_IV_LENGTH + self::ENCRYPTION_TAG_LENGTH;
            $encryptedFilePayload = substr($encryptedFileContent, $startOfPayload);
            if (strlen($encryptedFileContent) < $startOfPayload) {
                $this->io->error('file too small for the chosen algorithm.');
                return false;
            }

            $algorithm = substr($encryptedFileContent, 0, strlen(self::ENCRYPTION_ALGORITHM));
            if ($algorithm !== self::ENCRYPTION_ALGORITHM) {
                $this->io->error('unexpected algorithm.');
                return false;
            }

            $iv = substr($encryptedFileContent, strlen(self::ENCRYPTION_ALGORITHM), self::ENCRYPTION_IV_LENGTH);
            $tag = substr($encryptedFileContent, strlen(self::ENCRYPTION_ALGORITHM) + self::ENCRYPTION_IV_LENGTH, self::ENCRYPTION_TAG_LENGTH);
            $hashedKey = hash(self::ENCRYPTION_KEY_HASH_ALGORITHM, $key);
            $decrypted = openssl_decrypt($encryptedFilePayload, self::ENCRYPTION_ALGORITHM, $hashedKey, OPENSSL_RAW_DATA, $iv, $tag);

            if ($check) {
                if (file_exists($expectedFilePath)) {
                    $expectedContent = file_get_contents($expectedFilePath);
                    if ($expectedContent !== $decrypted) {
                        $checkFailedFiles[$configuredFileKey] = $expectedFilePath;
                    }
                }
            } else {
                file_put_contents($expectedFilePath, $decrypted);
            }
        }

        if ($missingFiles !== []) {
            $this->io->error('For instance ' . $instance->describe() . ' the required encrypted file(s) ' . implode(', ', array_keys($missingFiles)) . ' are missing, expected at ' . implode(', ', $missingFiles));
        }

        if ($checkFailedFiles !== []) {
            $this->io->error('For instance ' . $instance->describe() . ' the encrypted file(s) ' . implode(', ', array_keys($checkFailedFiles)) . ' differ to their decrypted versions, expected at ' . implode(', ', $checkFailedFiles));
        }

        if ($missingFiles !== [] || $checkFailedFiles !== []) {
            return false;
        }

        return true;
    }

    public function allRequiredFilesExist(Instance $instance): bool
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();
        $missingFiles = [];
        foreach ($configuredFiles as $configuredFile) {
            $configuredFileKey = $configuredFile->getPath();
            $expectedFilePath = $instanceConfigFolder . DIRECTORY_SEPARATOR . $configuredFileKey;

            if (!$configuredFile->getIsRequired()) {
                continue;
            }

            if ($configuredFile->getIsEncrypted()) {
                $expectedFilePath .= self::ENCRYPTED_FILE_EXTENSION;
            }

            if (!file_exists($expectedFilePath)) {
                $missingFiles[$configuredFileKey] = $expectedFilePath;
            }
        }

        if ($missingFiles !== []) {
            $this->io->error('For instance ' . $instance->describe() . ' the required file(s) ' . implode(', ', array_keys($missingFiles)) . ' are missing, expected at ' . implode(', ', $missingFiles));

            return false;
        }

        return true;
    }

    public function uploadFiles(Instance $instance, Installation $installation): void
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();
        foreach ($configuredFiles as $configuredFile) {
            $configuredFileKey = $configuredFile->getPath();
            $expectedFilePath = $instanceConfigFolder . DIRECTORY_SEPARATOR . $configuredFileKey;

            if (file_exists($expectedFilePath)) {
                $fullPath = $installation->getFolder() . DIRECTORY_SEPARATOR . $configuredFileKey;
                $folder = dirname($fullPath);
                $content = file_get_contents($expectedFilePath);
                $instance->getConnection()->createFolder($folder);
                $instance->getConnection()->writeFile($fullPath, $content);
            }
        }
    }

    private function getLocalConfigFolderPath(Instance $instance): string
    {
        $configFolder = $this->configurationService->getConfigFolder();

        return $configFolder . DIRECTORY_SEPARATOR .
            $instance->getServerName() . DIRECTORY_SEPARATOR .
            $instance->getEnvironmentName() . DIRECTORY_SEPARATOR .
            $instance->getStage();
    }
}
