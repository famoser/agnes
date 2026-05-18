<?php

namespace Agnes\Services;

use Agnes\Models\Installation;
use Agnes\Models\Instance;
use Symfony\Component\Console\Style\StyleInterface;

readonly class FileService
{
    public const ENCRYPTED_FILE_EXTENSION = '.encrypted';
    public const ENCRYPTION_ALGORITHM = 'aes-256-gcm';
    public const ENCRYPTION_IV_LENGTH = 12;
    public const ENCRYPTION_TAG_LENGTH = 16;
    public const ENCRYPTION_KEY_HASH_ALGORITHM = 'sha256';

    public function __construct(private StyleInterface $io, private ConfigurationService $configurationService)
    {
    }

    public function encrypt(Instance $instance, bool $overwrite): ?bool
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();
        foreach ($configuredFiles as $configuredFile) {
            $configuredFileKey = $configuredFile->getPath();
            $expectedFilePath = $instanceConfigFolder . DIRECTORY_SEPARATOR . $configuredFileKey;

            if (!$configuredFile->getIsEncrypted() || !file_exists($expectedFilePath)) {
                continue;
            }

            $encryptedFilePath = $expectedFilePath . self::ENCRYPTED_FILE_EXTENSION;
            $fileContent = file_get_contents($expectedFilePath);
            if (file_exists($encryptedFilePath) && !$overwrite) {
                if (!$this->decryptFile($encryptedFilePath, $decrypted, $error)) {
                    $this->io->error('Failed to decrypt file ' . $encryptedFilePath . ': ' . $error);
                    return false;
                }

                if ($fileContent === $decrypted) {
                    continue;
                }
            }

            if (!$this->encryptFile($encryptedFilePath, $fileContent, $error)) {
                $this->io->error('Failed to encrypt file ' . $expectedFilePath . ': ' . $error);
                return false;
            }
        }

        return true;
    }

    public function decrypt(Instance $instance, bool $diff): ?bool
    {
        $instanceConfigFolder = $this->getLocalConfigFolderPath($instance);

        $configuredFiles = $this->configurationService->getFiles();
        $missingFiles = [];
        $checkFailedFiles = [];

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

            if (!$this->decryptFile($expectedEncryptedFilePath, $decrypted, $error)) {
                $this->io->error('Failed to decrypt file ' . $expectedEncryptedFilePath . ': ' . $error);
                return false;
            }

            if (file_exists($expectedFilePath) && $diff) {
                $expectedContent = file_get_contents($expectedFilePath);
                if ($expectedContent !== $decrypted) {
                    $checkFailedFiles[$configuredFileKey] = $expectedFilePath;
                }

                continue;
            }

            file_put_contents($expectedFilePath, $decrypted);
        }

        if ($missingFiles !== []) {
            $this->io->error('For instance ' . $instance->describe() . ' the required encrypted file(s) ' . implode(', ', array_keys($missingFiles)) . ' are missing, expected at ' . implode(', ', $missingFiles));
        }

        if ($checkFailedFiles !== []) {
            $this->io->warning('For instance ' . $instance->describe() . ' the encrypted file(s) ' . implode(', ', array_keys($checkFailedFiles)) . ' differ to their decrypted versions, expected at ' . implode(', ', $checkFailedFiles));
        }

        if ($missingFiles !== [] || $checkFailedFiles !== []) {
            return false;
        }

        return true;
    }

    private function encryptFile(string $filepath, string $content, ?string &$error = null): bool
    {
        $key = $this->configurationService->getConfigEncryptionKey();
        if (!$key) {
            $error = 'No encryption key configured.';
            return false;
        }

        if (!extension_loaded('openssl')) {
            $error = 'openssl extension is not installed.';
            return false;
        }

        $iv = openssl_random_pseudo_bytes(self::ENCRYPTION_IV_LENGTH);
        $hashedKey = hash(self::ENCRYPTION_KEY_HASH_ALGORITHM, $key);
        $encrypted = openssl_encrypt($content, self::ENCRYPTION_ALGORITHM, $hashedKey, OPENSSL_RAW_DATA, $iv, $tag, tag_length: self::ENCRYPTION_TAG_LENGTH);

        file_put_contents($filepath, self::ENCRYPTION_ALGORITHM . $iv . $tag . $encrypted);

        return true;
    }

    private function decryptFile(string $filepath, ?string &$decrypted = null, ?string &$error = null): bool
    {
        $key = $this->configurationService->getConfigEncryptionKey();
        if (!$key) {
            $error = 'No encryption key configured.';
            return false;
        }

        if (!extension_loaded('openssl')) {
            $error = 'openssl extension is not installed.';
            return false;
        }

        $encryptedFileContent = file_get_contents($filepath);
        $startOfPayload = strlen(self::ENCRYPTION_ALGORITHM) + self::ENCRYPTION_IV_LENGTH + self::ENCRYPTION_TAG_LENGTH;
        $encryptedFilePayload = substr($encryptedFileContent, $startOfPayload);
        if (strlen($encryptedFileContent) < $startOfPayload) {
            $error = 'File too small for the chosen algorithm.';
            return false;
        }

        $algorithm = substr($encryptedFileContent, 0, strlen(self::ENCRYPTION_ALGORITHM));
        if ($algorithm !== self::ENCRYPTION_ALGORITHM) {
            $error = 'Unexpected algorithm.';
            return false;
        }

        $iv = substr($encryptedFileContent, strlen(self::ENCRYPTION_ALGORITHM), self::ENCRYPTION_IV_LENGTH);
        $tag = substr($encryptedFileContent, strlen(self::ENCRYPTION_ALGORITHM) + self::ENCRYPTION_IV_LENGTH, self::ENCRYPTION_TAG_LENGTH);
        $hashedKey = hash(self::ENCRYPTION_KEY_HASH_ALGORITHM, $key);
        $decrypted = openssl_decrypt($encryptedFilePayload, self::ENCRYPTION_ALGORITHM, $hashedKey, OPENSSL_RAW_DATA, $iv, $tag);

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
