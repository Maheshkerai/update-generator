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
            'tests',
            'vendor'
        ];

        $files = [
            '.env',
            '.env.example',
            '.editorconfig',
            '.gitattributes',
            '.gitignore',
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
                $this->copyDirectoryRecursively($sourceDir, $destDir, $excludePaths, $copiedCount, $dir);
            }
        }

        // Copy individual files
        foreach ($files as $file) {
            $sourceFile = $source . '/' . $file;
            $destFile = $destination . '/' . $file;
            
            if (is_file($sourceFile) && !$this->shouldSkipFile($file, $excludePaths)) {
                if ($this->safeCopy($sourceFile, $destFile)) {
                    $copiedCount++;
                    
                    // Sanitize .env file after copying
                    if ($file === '.env') {
                        $this->sanitizeEnvFile($destFile);
                    }
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
     * @param string $relativePath
     * @return void
     */
    private function copyDirectoryRecursively(string $source, string $destination, array $excludePaths, int &$copiedCount, string $relativePath = ''): void
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
            $currentRelativePath = $relativePath ? $relativePath . '/' . $file : $file;

            if ($this->shouldSkipFile($currentRelativePath, $excludePaths)) {
                continue;
            }

            if (is_dir($sourcePath)) {
                $this->copyDirectoryRecursively($sourcePath, $destPath, $excludePaths, $copiedCount, $currentRelativePath);
            } else {
                if ($this->safeCopy($sourcePath, $destPath)) {
                    $copiedCount++;
                }
            }
        }
    }

    /**
     * Create ZIP archive from directory using PHP ZipArchive
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

        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            throw new UpdateGeneratorException("ZipArchive class is not available. Please ensure the zip extension is installed.");
        }

        $zip = new ZipArchive();
        $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== TRUE) {
            throw new UpdateGeneratorException("Failed to create ZIP archive: {$zipPath}. Error code: {$result}");
        }

        try {
            // Add all files and directories recursively
            $this->addDirectoryToZip($zip, $sourcePath, '');
            
            if (!$zip->close()) {
                throw new UpdateGeneratorException("Failed to close ZIP archive: {$zipPath}");
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
        } catch (\Exception $e) {
            $zip->close();
            throw new UpdateGeneratorException("Failed to create ZIP archive: {$zipPath}. Error: " . $e->getMessage());
        }
    }

    /**
     * Add directory contents to ZIP archive recursively
     *
     * @param ZipArchive $zip
     * @param string $sourcePath
     * @param string $relativePath
     * @return void
     */
    private function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $relativePath): void
    {
        $files = scandir($sourcePath);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $sourcePath . '/' . $file;
            $zipPath = $relativePath ? $relativePath . '/' . $file : $file;
            
            if (is_dir($filePath)) {
                // Add empty directory
                $zip->addEmptyDir($zipPath);
                // Recursively add directory contents
                $this->addDirectoryToZip($zip, $filePath, $zipPath);
            } else {
                // Add file to zip
                $zip->addFile($filePath, $zipPath);
            }
        }
    }

    /**
     * Create nested ZIP archive using PHP ZipArchive
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

        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            throw new UpdateGeneratorException("ZipArchive class is not available. Please ensure the zip extension is installed.");
        }

        $zip = new ZipArchive();
        $result = $zip->open($finalZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== TRUE) {
            throw new UpdateGeneratorException("Failed to create final ZIP archive: {$finalZipPath}. Error code: {$result}");
        }

        try {
            // Add the source ZIP file
            $zip->addFile($sourceZipPath, 'source_code.zip');
            
            // Add the version info file
            $zip->addFile($versionInfoPath, 'version_info.php');
            
            if (!$zip->close()) {
                throw new UpdateGeneratorException("Failed to close final ZIP archive: {$finalZipPath}");
            }

            if (!file_exists($finalZipPath)) {
                throw new UpdateGeneratorException("Final ZIP file was not created: {$finalZipPath}");
            }

            if (config('update-generator.enable_logging', true)) {
                Log::info('Nested ZIP archive created successfully', [
                    'final_zip_path' => $finalZipPath,
                    'file_size' => filesize($finalZipPath)
                ]);
            }

            return true;
        } catch (\Exception $e) {
            $zip->close();
            throw new UpdateGeneratorException("Failed to create final ZIP archive: {$finalZipPath}. Error: " . $e->getMessage());
        }
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
     * Sanitize .env file by replacing sensitive values with default values
     *
     * @param string $envFilePath
     * @return void
     */
    public function sanitizeEnvFile(string $envFilePath): void
    {
        if (!config('update-generator.sanitize_env_file', true)) {
            return;
        }

        if (!File::exists($envFilePath)) {
            return;
        }

        $sanitizationRules = config('update-generator.env_sanitization_rules', []);
        
        if (empty($sanitizationRules)) {
            return;
        }

        $envContent = File::get($envFilePath);
        $lines = explode("\n", $envContent);
        $sanitizedLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                $sanitizedLines[] = $line;
                continue;
            }

            // Check if line contains a variable assignment
            if (str_contains($line, '=')) {
                $parts = explode('=', $line, 2);
                $variable = trim($parts[0]);
                $originalValue = isset($parts[1]) ? trim($parts[1]) : '';

                // Check if this variable should be sanitized
                if (isset($sanitizationRules[$variable])) {
                    $newValue = $sanitizationRules[$variable];
                    
                    // Handle special cases
                    if ($variable === 'APP_KEY' && $newValue === 'base64:your-app-key-here') {
                        // Generate a new APP_KEY for the installation
                        $newValue = 'base64:' . base64_encode(random_bytes(32));
                    }
                    
                    $sanitizedLines[] = $variable . '=' . $newValue;
                    
                    if (config('update-generator.enable_logging', true)) {
                        Log::info('Environment variable sanitized', [
                            'variable' => $variable,
                            'original_value' => $this->maskSensitiveValue($originalValue),
                            'new_value' => $this->maskSensitiveValue($newValue)
                        ]);
                    }
                } else {
                    $sanitizedLines[] = $line;
                }
            } else {
                $sanitizedLines[] = $line;
            }
        }

        // Write the sanitized content back to the file
        File::put($envFilePath, implode("\n", $sanitizedLines));

        if (config('update-generator.enable_logging', true)) {
            Log::info('.env file sanitized successfully', [
                'file_path' => $envFilePath,
                'rules_applied' => count($sanitizationRules)
            ]);
        }
    }

    /**
     * Mask sensitive values for logging
     *
     * @param string $value
     * @return string
     */
    private function maskSensitiveValue(string $value): string
    {
        if (empty($value)) {
            return '(empty)';
        }

        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
    }

    /**
     * Clear all cache files before generating packages
     *
     * @return void
     */
    public function clearCache(): void
    {
        $cachePaths = [
            'storage/framework/cache',
            'storage/framework/views',
            'storage/framework/sessions',
            'bootstrap/cache',
        ];

        foreach ($cachePaths as $cachePath) {
            $fullPath = base_path($cachePath);
            
            if (File::isDirectory($fullPath)) {
                // Clear cache directory contents but keep the directory structure
                $this->clearDirectoryContents($fullPath);
                
                if (config('update-generator.enable_logging', true)) {
                    Log::info('Cache directory cleared', ['path' => $cachePath]);
                }
            }
        }

        // Clear Laravel application cache using Artisan commands
        $this->runArtisanCommand('cache:clear');
        $this->runArtisanCommand('view:clear');
        $this->runArtisanCommand('config:clear');
        $this->runArtisanCommand('route:clear');

        if (config('update-generator.enable_logging', true)) {
            Log::info('All cache cleared successfully');
        }
    }

    /**
     * Clear directory contents but keep the directory structure
     *
     * @param string $directory
     * @return void
     */
    private function clearDirectoryContents(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $directory . '/' . $file;
            
            if (is_dir($filePath)) {
                File::deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }
    }

    /**
     * Run Artisan command
     *
     * @param string $command
     * @return void
     */
    private function runArtisanCommand(string $command): void
    {
        try {
            $output = [];
            $returnCode = 0;
            
            exec("php artisan {$command} 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                if (config('update-generator.enable_logging', true)) {
                    Log::warning('Artisan command failed', [
                        'command' => $command,
                        'output' => implode("\n", $output)
                    ]);
                }
            }
        } catch (\Exception $e) {
            if (config('update-generator.enable_logging', true)) {
                Log::warning('Failed to run Artisan command', [
                    'command' => $command,
                    'error' => $e->getMessage()
                ]);
            }
        }
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
            
            // Handle wildcard patterns (e.g., storage/framework/sessions/*)
            if (str_contains($excluded, '*')) {
                $pattern = preg_quote($excluded, '/');
                $pattern = str_replace('\*', '.+', $pattern);
                if (preg_match('/^' . $pattern . '$/', $file)) {
                    return true;
                }
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