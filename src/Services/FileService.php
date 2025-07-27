<?php

declare(strict_types=1);

namespace Mahesh\UpdateGenerator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Mahesh\UpdateGenerator\Exceptions\UpdateGeneratorException;
use ZipArchive;

final class FileService
{
    /**
     * Copy files to destination directory
     *
     * @param array<string> $files
     * @param string $destinationPath
     * @param array<string> $excludePaths
     * @return int Number of files copied
     * @throws UpdateGeneratorException
     */
    public function copyFiles(array $files, string $destinationPath, array $excludePaths = []): int
    {
        $copiedCount = 0;
        $basePath = base_path();

        // Ensure destination directory exists
        File::ensureDirectoryExists($destinationPath);

        foreach ($files as $file) {
            if (empty($file)) {
                continue;
            }

            if ($this->shouldSkipFile($file, $excludePaths)) {
                continue;
            }

            $sourcePath = $basePath . '/' . $file;
            $destPath = $destinationPath . '/' . $file;

            if ($this->safeCopy($sourcePath, $destPath)) {
                $copiedCount++;
            }
        }

        if (config('update-generator.enable_logging', true)) {
            Log::info('Files copied successfully', [
                'destination' => $destinationPath,
                'copied_count' => $copiedCount,
                'total_files' => count($files)
            ]);
        }

        return $copiedCount;
    }

    /**
     * Copy all files from source directory to destination
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @param array<string> $excludePaths
     * @return int Number of files copied
     * @throws UpdateGeneratorException
     */
    public function copyAllFiles(string $sourcePath, string $destinationPath, array $excludePaths = []): int
    {
        if (!File::exists($sourcePath)) {
            throw new UpdateGeneratorException("Source path does not exist: {$sourcePath}");
        }

        $copiedCount = 0;
        $files = File::allFiles($sourcePath);

        // Ensure destination directory exists
        File::ensureDirectoryExists($destinationPath);

        foreach ($files as $file) {
            $relativePath = str_replace($sourcePath . '/', '', $file->getPathname());
            
            if ($this->shouldSkipFile($relativePath, $excludePaths)) {
                continue;
            }

            $destPath = $destinationPath . '/' . $relativePath;
            
            if ($this->safeCopy($file->getPathname(), $destPath)) {
                $copiedCount++;
            }
        }

        if (config('update-generator.enable_logging', true)) {
            Log::info('All files copied successfully', [
                'source' => $sourcePath,
                'destination' => $destinationPath,
                'copied_count' => $copiedCount
            ]);
        }

        return $copiedCount;
    }

    /**
     * Create ZIP archive from directory
     *
     * @param string $sourcePath
     * @param string $zipPath
     * @return bool
     * @throws UpdateGeneratorException
     */
    public function createZip(string $sourcePath, string $zipPath): bool
    {
        if (!File::exists($sourcePath)) {
            throw new UpdateGeneratorException("Source path does not exist: {$sourcePath}");
        }

        $zip = new ZipArchive();
        $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== ZipArchive::ER_OK) {
            throw new UpdateGeneratorException("Failed to create ZIP archive: {$zipPath}");
        }

        $files = File::allFiles($sourcePath);
        
        foreach ($files as $file) {
            $relativePath = str_replace($sourcePath . '/', '', $file->getPathname());
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();

        if (config('update-generator.enable_logging', true)) {
            Log::info('ZIP archive created successfully', [
                'source' => $sourcePath,
                'zip_path' => $zipPath,
                'file_count' => count($files)
            ]);
        }

        return true;
    }

    /**
     * Create nested ZIP archive
     *
     * @param string $sourceZipPath
     * @param string $versionInfoPath
     * @param string $finalZipPath
     * @return bool
     * @throws UpdateGeneratorException
     */
    public function createNestedZip(string $sourceZipPath, string $versionInfoPath, string $finalZipPath): bool
    {
        if (!File::exists($sourceZipPath)) {
            throw new UpdateGeneratorException("Source ZIP does not exist: {$sourceZipPath}");
        }

        if (!File::exists($versionInfoPath)) {
            throw new UpdateGeneratorException("Version info file does not exist: {$versionInfoPath}");
        }

        $zip = new ZipArchive();
        $result = $zip->open($finalZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== ZipArchive::ER_OK) {
            throw new UpdateGeneratorException("Failed to create final ZIP archive: {$finalZipPath}");
        }

        $zip->addFile($sourceZipPath, 'source_code.zip');
        $zip->addFile($versionInfoPath, 'version_info.php');
        $zip->close();

        if (config('update-generator.enable_logging', true)) {
            Log::info('Nested ZIP archive created successfully', [
                'final_zip_path' => $finalZipPath
            ]);
        }

        return true;
    }

    /**
     * Create version info file
     *
     * @param string $currentVersion
     * @param string $updateVersion
     * @param string $filePath
     * @return bool
     */
    public function createVersionInfo(string $currentVersion, string $updateVersion, string $filePath): bool
    {
        $content = "<?php\nreturn array('current_version' => '{$currentVersion}','update_version' => '{$updateVersion}');";
        
        $result = File::put($filePath, $content);

        if (config('update-generator.enable_logging', true)) {
            Log::info('Version info file created', [
                'file_path' => $filePath,
                'current_version' => $currentVersion,
                'update_version' => $updateVersion
            ]);
        }

        return $result !== false;
    }

    /**
     * Clean up temporary files
     *
     * @param array<string> $filePaths
     * @return void
     */
    public function cleanup(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            if (File::exists($filePath)) {
                if (File::isDirectory($filePath)) {
                    File::deleteDirectory($filePath);
                } else {
                    File::delete($filePath);
                }
            }
        }

        if (config('update-generator.enable_logging', true)) {
            Log::info('Cleanup completed', [
                'cleaned_files' => $filePaths
            ]);
        }
    }

    /**
     * Check if file should be skipped
     *
     * @param string $file
     * @param array<string> $excludePaths
     * @return bool
     */
    private function shouldSkipFile(string $file, array $excludePaths): bool
    {
        foreach ($excludePaths as $excluded) {
            if (str_starts_with($file, $excluded)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Safely copy file or directory
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    private function safeCopy(string $source, string $destination): bool
    {
        try {
            if (File::isFile($source)) {
                File::ensureDirectoryExists(dirname($destination));
                return File::copy($source, $destination);
            } elseif (File::isDirectory($source)) {
                File::ensureDirectoryExists($destination);
                return File::copyDirectory($source, $destination);
            }
        } catch (\Exception $e) {
            if (config('update-generator.enable_logging', true)) {
                Log::warning('Failed to copy file', [
                    'source' => $source,
                    'destination' => $destination,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return false;
    }
} 