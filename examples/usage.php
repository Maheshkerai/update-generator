<?php

declare(strict_types=1);

/**
 * Example usage of the Update Generator package
 * 
 * This file demonstrates various ways to use the package
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Mahesh\UpdateGenerator\Exceptions\GitException;
use Mahesh\UpdateGenerator\Exceptions\UpdateGeneratorException;
use Mahesh\UpdateGenerator\Helpers\UpdateHelper;
use Mahesh\UpdateGenerator\Services\FileService;
use Mahesh\UpdateGenerator\Services\GitService;
use Mahesh\UpdateGenerator\Services\UpdateGeneratorService;

echo "🚀 Update Generator Package Examples\n";
echo "=====================================\n\n";

// Example 1: Using the Helper Class (Recommended)
echo "1. Using Helper Class (Recommended)\n";
echo "-----------------------------------\n";

try {
    // Generate update package
    $updateFiles = UpdateHelper::prepareUpdateFiles(
        '1.0.0',      // current version
        '1.1.0',      // update version
        '2025-01-01', // from date
        '2025-03-31'  // to date
    );
    
    echo "✅ Update package generated: " . implode(', ', $updateFiles) . "\n";
    
    // Generate new installation package
    $installationFiles = UpdateHelper::prepareNewInstallationFiles('1.1.0');
    
    echo "✅ New installation package generated: " . implode(', ', $installationFiles) . "\n";
    
} catch (GitException $e) {
    echo "❌ Git Error: " . $e->getMessage() . "\n";
} catch (UpdateGeneratorException $e) {
    echo "❌ Update Generator Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 2: Using Service Classes
echo "2. Using Service Classes\n";
echo "------------------------\n";

try {
    $gitService = new GitService();
    $fileService = new FileService();
    $updateGenerator = new UpdateGeneratorService($gitService, $fileService);
    
    // Generate both packages
    $allFiles = $updateGenerator->generateBoth(
        '2025-01-01', // start date
        '2025-03-31', // end date
        '1.0.0',      // current version
        '1.1.0'       // update version
    );
    
    echo "✅ Both packages generated: " . implode(', ', $allFiles) . "\n";
    
} catch (GitException $e) {
    echo "❌ Git Error: " . $e->getMessage() . "\n";
} catch (UpdateGeneratorException $e) {
    echo "❌ Update Generator Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 3: Error Handling
echo "3. Error Handling Examples\n";
echo "--------------------------\n";

// Test invalid version
try {
    UpdateHelper::prepareNewInstallationFiles('invalid-version');
} catch (UpdateGeneratorException $e) {
    echo "✅ Caught invalid version error: " . $e->getMessage() . "\n";
}

// Test same versions
try {
    UpdateHelper::prepareUpdateFiles('1.0.0', '1.0.0', '2025-01-01', '2025-03-31');
} catch (UpdateGeneratorException $e) {
    echo "✅ Caught same version error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 4: Configuration
echo "4. Configuration Example\n";
echo "------------------------\n";

// You can customize the configuration
$config = [
    'exclude_update' => [
        'storage',
        'vendor',
        '.env',
        'node_modules',
        '.git',
        'tests',
        'phpunit.xml',
    ],
    'exclude_new' => [
        'storage',
        'vendor',
        '.env',
        'node_modules',
        '.git',
        'tests',
        'phpunit.xml',
    ],
    'output_directory' => 'storage/app/update_files',
    'git_timeout' => 300,
    'enable_logging' => true,
];

echo "✅ Configuration loaded with " . count($config['exclude_update']) . " excluded paths\n";

echo "\n";

// Example 5: Command Line Usage
echo "5. Command Line Usage\n";
echo "---------------------\n";

echo "Generate both packages:\n";
echo "php artisan update:generate --start_date=2025-01-01 --end_date=2025-03-31 --current_version=1.0.0 --update_version=1.1.0 --type=both\n\n";

echo "Generate only update package:\n";
echo "php artisan update:generate --start_date=2025-01-01 --end_date=2025-03-31 --current_version=1.0.0 --update_version=1.1.0 --type=update\n\n";

echo "Generate only new installation package:\n";
echo "php artisan update:generate --update_version=1.1.0 --type=new\n\n";

echo "🎉 Examples completed!\n";
echo "For more information, check the README.md file.\n"; 