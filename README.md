# Update Generator

A robust Laravel package for generating update ZIP files and new installation packages based on Git repository changes.

## Features

- 🚀 **Git Integration**: Automatically detects files changed between specified dates
- 📦 **Multiple Package Types**: Generate update packages, new installation packages, or both
- 🛡️ **Security**: Safe Git command execution with proper validation
- 📝 **Logging**: Comprehensive logging for debugging and monitoring
- ⚙️ **Configurable**: Easy configuration for excluded paths and settings
- 🔧 **Error Handling**: Robust error handling with custom exceptions
- 🎯 **Type Safety**: Full PHP 8.1+ type safety with strict typing

## Minimum Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | 8.1+    |
| Laravel     | 9.x+    |
| Git         | 2.0+    |

## Installation

### 1. Install the package

```bash
composer require mahesh-kerai/update-generator
```

### 2. Publish configuration

```bash
php artisan vendor:publish --tag=config
```

This creates the configuration file at `config/update-generator.php`

### 3. Configure excluded paths

Edit `config/update-generator.php` to customize excluded paths:

```php
return [
    'exclude_update' => [
        'storage',
        'vendor',
        '.env',
        'node_modules',
        '.git',
        '.idea',
        'composer.lock',
        'package-lock.json',
        'yarn.lock',
        'public/storage',
        'public/uploads',
        'tests',
        'phpunit.xml',
        '.gitignore',
        '.env.example',
        'README.md',
        'CHANGELOG.md',
    ],
    'exclude_new' => [
        // Same as exclude_update or customize as needed
    ],
    'output_directory' => 'storage/app/update_files',
    'git_timeout' => 300,
    'enable_logging' => true,
];
```

## Usage

### Artisan Command

Generate both update and new installation packages:

```bash
php artisan update:generate \
    --start_date=2025-01-01 \
    --end_date=2025-03-31 \
    --current_version=1.0.0 \
    --update_version=1.1.0 \
    --type=both
```

Generate only update package:

```bash
php artisan update:generate \
    --start_date=2025-01-01 \
    --end_date=2025-03-31 \
    --current_version=1.0.0 \
    --update_version=1.1.0 \
    --type=update
```

Generate only new installation package:

```bash
php artisan update:generate \
    --update_version=1.1.0 \
    --type=new
```

### Programmatic Usage

#### Using the Helper Class (Recommended)

```php
use Mahesh\UpdateGenerator\Helpers\UpdateHelper;

// Generate update package
$updateFiles = UpdateHelper::prepareUpdateFiles(
    '1.0.0',    // current version
    '1.1.0',    // update version
    '2025-01-01', // from date
    '2025-03-31'  // to date
);

// Generate new installation package
$installationFiles = UpdateHelper::prepareNewInstallationFiles('1.1.0');

// Generate both packages
$allFiles = UpdateHelper::prepareBothPackages(
    '1.0.0',    // current version
    '1.1.0',    // update version
    '2025-01-01', // from date
    '2025-03-31'  // to date
);
```

#### Using the Service Class

```php
use Mahesh\UpdateGenerator\Services\UpdateGeneratorService;
use Mahesh\UpdateGenerator\Services\GitService;
use Mahesh\UpdateGenerator\Services\FileService;

$gitService = new GitService();
$fileService = new FileService();
$updateGenerator = new UpdateGeneratorService($gitService, $fileService);

// Generate update package
$updateFiles = $updateGenerator->generateUpdate(
    '2025-01-01', // start date
    '2025-03-31', // end date
    '1.0.0',      // current version
    '1.1.0'       // update version
);

// Generate new installation package
$installationFiles = $updateGenerator->generateNewInstallation('1.1.0');
```

#### Using the Facade

```php
use Mahesh\UpdateGenerator\Facades\UpdateGenerator;

$updateFiles = UpdateGenerator::generateUpdate(
    '2025-01-01', // start date
    '2025-03-31', // end date
    '1.0.0',      // current version
    '1.1.0'       // update version
);
```

## Command Options

| Option | Description | Required | Default |
|--------|-------------|----------|---------|
| `--start_date` | Start date (YYYY-MM-DD) | For update/both | - |
| `--end_date` | End date (YYYY-MM-DD) | For update/both | - |
| `--current_version` | Current version number | For update/both | - |
| `--update_version` | New version number | Always | - |
| `--type` | Package type (update/new/both) | No | both |

## Output Structure

### Update Package
```
Update 1.0.0-to-1.1.0.zip
├── source_code.zip (contains changed files)
└── version_info.php (version information)
```

### New Installation Package
```
New_Installation_V1.1.0.zip
└── [all project files except excluded]
```

### Version Info File
```php
<?php
return [
    'current_version' => '1.0.0',
    'update_version' => '1.1.0',
];
```

## Configuration Options

| Option | Description | Default |
|--------|-------------|---------|
| `exclude_update` | Paths to exclude from update packages | See config |
| `exclude_new` | Paths to exclude from new installation packages | See config |
| `output_directory` | Directory for generated packages | `storage/app/update_files` |
| `git_timeout` | Git command timeout in seconds | `300` |
| `enable_logging` | Enable logging for debugging | `true` |

## Error Handling

The package provides comprehensive error handling with custom exceptions:

```php
use Mahesh\UpdateGenerator\Exceptions\GitException;
use Mahesh\UpdateGenerator\Exceptions\UpdateGeneratorException;

try {
    $files = UpdateHelper::prepareUpdateFiles('1.0.0', '1.1.0', '2025-01-01', '2025-03-31');
} catch (GitException $e) {
    // Handle Git-related errors (not a Git repo, Git not installed, etc.)
    echo "Git Error: " . $e->getMessage();
} catch (UpdateGeneratorException $e) {
    // Handle update generator errors (invalid versions, no files found, etc.)
    echo "Update Generator Error: " . $e->getMessage();
}
```

## Logging

When logging is enabled, the package logs important events:

- Git command execution results
- File copy operations
- ZIP creation processes
- Error conditions
- Package generation completion

Logs are written to Laravel's default log channel.

## Best Practices

1. **Version Naming**: Use semantic versioning (e.g., 1.0.0, 1.1.0)
2. **Date Format**: Always use YYYY-MM-DD format for dates
3. **Git Repository**: Ensure you're in a Git repository before running commands
4. **Exclusions**: Configure appropriate exclusions for your project
5. **Testing**: Test generated packages in a staging environment before production use

## Troubleshooting

### Common Issues

1. **"Not a Git repository" error**
   - Ensure you're in a Git repository directory
   - Run `git init` if needed

2. **"No files found" error**
   - Check that the date range contains commits
   - Verify Git history with `git log --oneline`

3. **Permission errors**
   - Ensure write permissions to the output directory
   - Check file permissions in the project

4. **Timeout errors**
   - Increase `git_timeout` in configuration
   - Check Git repository size and history

### Debug Mode

Enable debug mode to get detailed error information:

```bash
php artisan update:generate --verbose
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support and questions:

- Email: [maheshkerai001@gmail.com](mailto:maheshkerai001@gmail.com)
- Issues: [GitHub Issues](https://github.com/mahesh-kerai/update-generator/issues)

---

**Made with ❤️ by Mahesh Kerai** 