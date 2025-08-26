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

        // Get additional files to include
        $additionalFiles = config('update-generator.add_update_file', []);

        // Ensure destination directory exists
        File::ensureDirectoryExists($destinationPath);

        // First, copy the main files
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

        // Then, copy additional files that should be included
        foreach ($additionalFiles as $additionalFile) {
            if (empty($additionalFile)) {
                continue;
            }

            $sourcePath = $basePath . '/' . $additionalFile;
            $destPath = $destinationPath . '/' . $additionalFile;

            if (File::exists($sourcePath)) {
                if ($this->safeCopy($sourcePath, $destPath)) {
                    $copiedCount++;
                    if (config('update-generator.enable_logging', true)) {
                        Log::info('Additional file included in update package', [
                            'file' => $additionalFile,
                            'destination' => $destinationPath
                        ]);
                    }
                }
            }
        }

        if (config('update-generator.enable_logging', true)) {
            Log::info('Files copied successfully', [
                'destination' => $destinationPath,
                'copied_count' => $copiedCount,
                'total_files' => count($files) + count($additionalFiles)
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

        // Prevent infinite loops by checking if destination is inside source
        $realSourcePath = realpath($sourcePath);
        $realDestPath = realpath($destinationPath);
        
        if ($realDestPath && str_starts_with($realDestPath, $realSourcePath)) {
            throw new UpdateGeneratorException("Destination path cannot be inside source path to prevent infinite loops");
        }

        $copiedCount = 0;
        
        // Use a different approach for new installation - copy specific directories and files
        $this->copyInstallationFiles($sourcePath, $destinationPath, $excludePaths, $copiedCount);

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
     * Copy installation files (specific approach to avoid infinite loops)
     *
     * @param string $source
     * @param string $destination
     * @param array<string> $excludePaths
     * @param int $copiedCount
     * @return void
     */
    private function copyInstallationFiles(string $source, string $destination, array $excludePaths, int &$copiedCount): void
    {
        // Define the main directories and files to copy for a new installation
        $directories = [
            'app',
            'bootstrap',
            'config',
            'database',
            'lang',
            'public',
            'resources',
            'routes',
            'storage',
            'tests'
        ];

        $files = [
            '.env',
            '.env.example',
            'artisan',
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'phpunit.xml',
            'README.md',
            'webpack.mix.js',
            'vite.config.js'
        ];

        // Ensure destination directory exists
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        // Copy directories
        foreach ($directories as $dir) {
            $sourceDir = $source . '/' . $dir;
            $destDir = $destination . '/' . $dir;
            
            if (is_dir($sourceDir) && !$this->shouldSkipFile($dir, $excludePaths)) {
                $this->copyDirectoryRecursively($sourceDir, $destDir, $excludePaths, $copiedCount);
            }
        }

        // Copy individual files
        foreach ($files as $file) {
            $sourceFile = $source . '/' . $file;
            $destFile = $destination . '/' . $file;
            
            if (is_file($sourceFile) && !$this->shouldSkipFile($file, $excludePaths)) {
                if ($this->safeCopy($sourceFile, $destFile)) {
                    $copiedCount++;
                }
            }
        }
    }

    /**
     * Recursively copy directory contents
     *
     * @param string $source
     * @param string $destination
     * @param array<string> $excludePaths
     * @param int $copiedCount
     * @return void
     */
    private function copyDirectoryRecursively(string $source, string $destination, array $excludePaths, int &$copiedCount): void
    {
        if (!is_dir($source)) {
            return;
        }

        // Prevent infinite loops by checking if we're trying to copy the destination into itself
        $realSourcePath = realpath($source);
        $realDestPath = realpath($destination);
        
        if ($realDestPath && str_starts_with($realDestPath, $realSourcePath)) {
            return; // Skip this directory to prevent infinite loops
        }

        // Ensure destination directory exists
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = scandir($source);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            if ($this->shouldSkipFile($file, $excludePaths)) {
                continue;
            }

            if (is_dir($sourcePath)) {
                $this->copyDirectoryRecursively($sourcePath, $destPath, $excludePaths, $copiedCount);
            } else {
                if ($this->safeCopy($sourcePath, $destPath)) {
                    $copiedCount++;
                }
            }
        }
    }

    /**
     * Create ZIP archive from directory using command line zip
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

        // Ensure the zip directory exists
        File::ensureDirectoryExists(dirname($zipPath));

        // Remove existing zip file if it exists
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        // Change to source directory and create zip
        $currentDir = getcwd();
        chdir($sourcePath);
        
        // Use command line zip to create archive
        $command = sprintf(
            'zip -r %s . 2>&1',
            escapeshellarg($zipPath)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        // Return to original directory
        chdir($currentDir);
        
        if ($returnCode !== 0) {
            throw new UpdateGeneratorException("Failed to create ZIP archive: {$zipPath}. Output: " . implode("\n", $output));
        }

        if (!file_exists($zipPath)) {
            throw new UpdateGeneratorException("ZIP file was not created: {$zipPath}");
        }

        if (config('update-generator.enable_logging', true)) {
            Log::info('ZIP archive created successfully', [
                'source' => $sourcePath,
                'zip_path' => $zipPath,
                'file_size' => filesize($zipPath)
            ]);
        }

        return true;
    }

    /**
     * Create nested ZIP archive using command line zip
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

        // Ensure the zip directory exists
        File::ensureDirectoryExists(dirname($finalZipPath));

        // Remove existing zip file if it exists
        if (file_exists($finalZipPath)) {
            unlink($finalZipPath);
        }

        // Create temporary directory for nested zip
        $tempDir = dirname($finalZipPath) . '/temp_nested_' . uniqid();
        File::makeDirectory($tempDir, 0755, true);

        try {
            // Copy files to temp directory
            copy($sourceZipPath, $tempDir . '/source_code.zip');
            copy($versionInfoPath, $tempDir . '/version_info.php');

            // Change to temp directory and create zip
            $currentDir = getcwd();
            chdir($tempDir);
            
            $command = sprintf(
                'zip %s source_code.zip version_info.php 2>&1',
                escapeshellarg($finalZipPath)
            );
            
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            // Return to original directory
            chdir($currentDir);
            
            if ($returnCode !== 0) {
                throw new UpdateGeneratorException("Failed to create final ZIP archive: {$finalZipPath}. Output: " . implode("\n", $output));
            }

            if (!file_exists($finalZipPath)) {
                throw new UpdateGeneratorException("Final ZIP file was not created: {$finalZipPath}");
            }

        } finally {
            // Clean up temp directory
            File::deleteDirectory($tempDir);
        }

        if (config('update-generator.enable_logging', true)) {
            Log::info('Nested ZIP archive created successfully', [
                'final_zip_path' => $finalZipPath,
                'file_size' => filesize($finalZipPath)
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
            // For exact file matches (like .env), check if the file name matches exactly
            if ($file === $excluded) {
                return true;
            }
            // For directory paths, check if the file starts with the excluded path
            if (str_starts_with($file, $excluded . '/')) {
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