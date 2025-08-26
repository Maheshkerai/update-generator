<?php

declare(strict_types=1);

namespace Mahesh\UpdateGenerator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Mahesh\UpdateGenerator\Exceptions\GitException;
use Mahesh\UpdateGenerator\Exceptions\UpdateGeneratorException;

final class UpdateGeneratorService
{
    public function __construct(
        private readonly GitService $gitService,
        private readonly FileService $fileService
    ) {}

    /**
     * Generate update package
     *
     * @param string $startDate
     * @param string $endDate
     * @param string $currentVersion
     * @param string $updateVersion
     * @return array<string> Generated file paths
     * @throws GitException|UpdateGeneratorException
     */
    public function generateUpdate(string $startDate, string $endDate, string $currentVersion, string $updateVersion): array
    {
        $this->validateVersions($currentVersion, $updateVersion);

        $outputDir = $this->getOutputDirectory();
        $updatePath = $outputDir . '/update_temp';
        $versionInfoPath = $outputDir . '/version_info.php';
        $sourceZipPath = $outputDir . '/source_code.zip';
        $finalZipPath = $outputDir . "/Update {$currentVersion}-to-{$updateVersion}.zip";

        try {
            // Get changed files from Git
            $changedFiles = $this->gitService->getChangedFiles($startDate, $endDate);

            if (empty($changedFiles)) {
                throw new UpdateGeneratorException('No files found for the specified date range');
            }

            // Copy changed files
            $excludePaths = config('update-generator.exclude_update', []);
            $copiedCount = $this->fileService->copyFiles($changedFiles, $updatePath, $excludePaths);

            if ($copiedCount === 0) {
                throw new UpdateGeneratorException('No files were copied after applying exclusions');
            }

            // Copy composer.json
            $this->fileService->copyFiles(['composer.json'], $updatePath);

            // Create version info file
            $this->fileService->createVersionInfo($currentVersion, $updateVersion, $versionInfoPath);

            // Create source code ZIP
            $this->fileService->createZip($updatePath, $sourceZipPath);

            // Create final nested ZIP
            $this->fileService->createNestedZip($sourceZipPath, $versionInfoPath, $finalZipPath);

            // Cleanup temporary files
            $this->fileService->cleanup([$updatePath, $sourceZipPath, $versionInfoPath]);

            if (config('update-generator.enable_logging', true)) {
                Log::info('Update package generated successfully', [
                    'current_version' => $currentVersion,
                    'update_version' => $updateVersion,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'files_processed' => count($changedFiles),
                    'files_copied' => $copiedCount,
                    'final_zip' => $finalZipPath
                ]);
            }

            return [$finalZipPath];

        } catch (\Exception $e) {
            // Cleanup on error
            $this->fileService->cleanup([$updatePath, $sourceZipPath, $versionInfoPath]);
            throw $e;
        }
    }

    /**
     * Generate new installation package
     *
     * @param string $version
     * @return array<string> Generated file paths
     * @throws UpdateGeneratorException
     */
    public function generateNewInstallation(string $version): array
    {
        $this->validateVersion($version);

        $outputDir = $this->getOutputDirectory();
        // Use system temp directory to avoid infinite loops
        $newPath = sys_get_temp_dir() . '/laravel_installation_' . uniqid();
        $finalZipPath = $outputDir . "/New_Installation_V{$version}.zip";

        try {
            // Copy all files
            $excludePaths = config('update-generator.exclude_new', []);
            $copiedCount = $this->fileService->copyAllFiles(base_path(), $newPath, $excludePaths);

            if ($copiedCount === 0) {
                throw new UpdateGeneratorException('No files were copied after applying exclusions');
            }

            // Create ZIP
            $this->fileService->createZip($newPath, $finalZipPath);

            // Cleanup temporary files
            $this->fileService->cleanup([$newPath]);

            if (config('update-generator.enable_logging', true)) {
                Log::info('New installation package generated successfully', [
                    'version' => $version,
                    'files_copied' => $copiedCount,
                    'final_zip' => $finalZipPath
                ]);
            }

            return [$finalZipPath];

        } catch (\Exception $e) {
            // Cleanup on error
            $this->fileService->cleanup([$newPath]);
            throw $e;
        }
    }

    /**
     * Generate both update and new installation packages
     *
     * @param string $startDate
     * @param string $endDate
     * @param string $currentVersion
     * @param string $updateVersion
     * @return array<string> Generated file paths
     * @throws GitException|UpdateGeneratorException
     */
    public function generateBoth(string $startDate, string $endDate, string $currentVersion, string $updateVersion): array
    {
        $updateFiles = $this->generateUpdate($startDate, $endDate, $currentVersion, $updateVersion);
        $installationFiles = $this->generateNewInstallation($updateVersion);

        return array_merge($updateFiles, $installationFiles);
    }

    /**
     * Get output directory
     *
     * @return string
     */
    private function getOutputDirectory(): string
    {
        $outputDir = config('update-generator.output_directory', 'storage/app/update_files');
        
        // Convert relative path to absolute if needed
        if (!str_starts_with($outputDir, '/')) {
            $outputDir = base_path($outputDir);
        }

        File::ensureDirectoryExists($outputDir);
        
        return $outputDir;
    }

    /**
     * Validate version format
     *
     * @param string $version
     * @throws UpdateGeneratorException
     */
    private function validateVersion(string $version): void
    {
        if (empty($version) || !preg_match('/^[\d.]+$/', $version)) {
            throw new UpdateGeneratorException('Invalid version format. Use format like 1.0.0');
        }
    }

    /**
     * Validate both versions
     *
     * @param string $currentVersion
     * @param string $updateVersion
     * @throws UpdateGeneratorException
     */
    private function validateVersions(string $currentVersion, string $updateVersion): void
    {
        $this->validateVersion($currentVersion);
        $this->validateVersion($updateVersion);

        if ($currentVersion === $updateVersion) {
            throw new UpdateGeneratorException('Current version and update version cannot be the same');
        }
    }
} 