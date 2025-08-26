<?php

declare(strict_types=1);

namespace Mahesh\UpdateGenerator\Facades;

use Illuminate\Support\Facades\Facade;
use Mahesh\UpdateGenerator\Services\UpdateGeneratorService;

/**
 * @method static array generateUpdate(string $startDate, string $endDate, string $currentVersion, string $updateVersion)
 * @method static array generateNewInstallation(string $version)
 * @method static array generateBoth(string $startDate, string $endDate, string $currentVersion, string $updateVersion)
 */
final class UpdateGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UpdateGeneratorService::class;
    }
} 