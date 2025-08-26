<?php

declare(strict_types=1);

namespace Mahesh\UpdateGenerator\Helpers;

use Mahesh\UpdateGenerator\Exceptions\GitException;
use Mahesh\UpdateGenerator\Exceptions\UpdateGeneratorException;
use Mahesh\UpdateGenerator\Services\FileService;
use Mahesh\UpdateGenerator\Services\GitService;
use Mahesh\UpdateGenerator\Services\UpdateGeneratorService;

/**
 * Helper class for generating update packages
 * 
 * This class provides static methods for easy usage as mentioned in the README
 */
final class UpdateHelper
{
    private static ?UpdateGeneratorService $service = null;

    /**
     * Get the UpdateGeneratorService instance
     *
     * @return UpdateGeneratorService
     */
    private static function getService(): UpdateGeneratorService
    {
        if (self::$service === null) {
            $gitService = new GitService();
            $fileService = new FileService();
            self::$service = new UpdateGeneratorService($gitService, $fileService);
        }

        return self::$service;
    }

    /**
     * Generate update package (static method for backward compatibility)
     *
     * @param string $currentVersion
     * @param string $updateVersion
     * @param string $fromDate
     * @param string $toDate
     * @return array<string>
     * @throws GitException|UpdateGeneratorException
     */
    public static function prepareUpdateFiles(string $currentVersion, string $updateVersion, string $fromDate, string $toDate): array
    {
        return self::getService()->generateUpdate($fromDate, $toDate, $currentVersion, $updateVersion);
    }

    /**
     * Generate new installation package (static method for backward compatibility)
     *
     * @param string $version
     * @return array<string>
     * @throws UpdateGeneratorException
     */
    public static function prepareNewInstallationFiles(string $version): array
    {
        return self::getService()->generateNewInstallation($version);
    }

    /**
     * Generate both update and new installation packages
     *
     * @param string $currentVersion
     * @param string $updateVersion
     * @param string $fromDate
     * @param string $toDate
     * @return array<string>
     * @throws GitException|UpdateGeneratorException
     */
    public static function prepareBothPackages(string $currentVersion, string $updateVersion, string $fromDate, string $toDate): array
    {
        return self::getService()->generateBoth($fromDate, $toDate, $currentVersion, $updateVersion);
    }

    /**
     * Legacy method for backward compatibility
     *
     * @param string $startDate
     * @param string $endDate
     * @param string $currentVersion
     * @param string $updateVersion
     * @return void
     * @throws GitException|UpdateGeneratorException
     * @deprecated Use prepareUpdateFiles() instead
     */
    public function generateUpdate(string $startDate, string $endDate, string $currentVersion, string $updateVersion): void
    {
        self::prepareUpdateFiles($currentVersion, $updateVersion, $startDate, $endDate);
    }

    /**
     * Legacy method for backward compatibility
     *
     * @param string $updateVersion
     * @return void
     * @throws UpdateGeneratorException
     * @deprecated Use prepareNewInstallationFiles() instead
     */
    public function generateNewInstallation(string $updateVersion): void
    {
        self::prepareNewInstallationFiles($updateVersion);
    }
}
