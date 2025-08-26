# Update Generator

A robust Laravel package for generating update ZIP files and new installation packages based on Git repository changes.

## Features

- 🚀 **Git Integration**: Automatically detects files changed between specified dates
- 📦 **Multiple Package Types**: Generate update packages, new installation packages, or both
- 🛡️ **Security**: Safe Git command execution with proper validation
- 📝 **Logging**: Comprehensive logging for debugging and monitoring
- ⚙️ **Configurable**: Easy configuration for excluded paths and settings
- 🎯 **Type Safety**: Full PHP 8.1+ type safety with strict typing
- 📁 **Custom File Inclusion**: Explicitly include custom packages and essential files in updates

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

### 3. Configure excluded paths and additional files

Edit `config/update-generator.php` to customize excluded paths and include additional files:

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
    
    /*
    |--------------------------------------------------------------------------
    | Additional files to include in update packages
    |--------------------------------------------------------------------------
    |
    | These files/folders will be explicitly included in update packages
    | even if they are in excluded paths (e.g., custom vendor packages)
    |
    */
    'add_update_file' => [
        'vendor/autoload.php',
        'vendor/mahesh-kerai',
        'vendor/composer',
    ],
    
    'exclude_new' => [
        'storage',
        'vendor',
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
| `add_update_file` | Files/folders to explicitly include in update packages | See config |
| `exclude_new` | Paths to exclude from new installation packages | See config |
| `output_directory` | Directory for generated packages | `storage/app/update_files` |
| `git_timeout` | Git command timeout in seconds | `300` |
| `enable_logging` | Enable logging for debugging | `true` |

### Additional Files Configuration

The `add_update_file` option allows you to explicitly include specific files or folders in your update packages, even if they're in excluded paths. This is particularly useful for:

- **Custom vendor packages** that need to be included in updates
- **Essential configuration files** like `vendor/autoload.php`
- **Composer files** required for proper package management
- **Any specific files** that should always be included

**Example:**
```php
'add_update_file' => [
    'vendor/autoload.php',           // Essential for Composer autoloading
    'vendor/mahesh-kerai',           // Your custom package
    'vendor/composer',               // Composer configuration
    'config/custom-config.php',      // Custom configuration
    'app/Services/CustomService.php' // Custom service
],
```


## Best Practices

1. **Version Naming**: Use semantic versioning (e.g., 1.0.0, 1.1.0)
2. **Date Format**: Always use YYYY-MM-DD format for dates
3. **Git Repository**: Ensure you're in a Git repository before running commands
4. **Exclusions**: Configure appropriate exclusions for your project
5. **Custom Files**: Use `add_update_file` to include essential custom packages and dependencies
6. **Testing**: Test generated packages in a staging environment before production use



## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 💡 Support  

👨‍💻 **Created by Mahesh Kerai** – A passionate Laravel developer who loves building clean and scalable solutions.  
🌟 *“Helping developers save time with automation and smarter workflows.”*  

📬 For questions, feedback, or support:  
- ✉️ Email: [wrteam.mahesh@gmail.com](mailto:wrteam.mahesh@gmail.com)  

---  
✨ Made with ❤️, ☕, and a lot of Laravel magic by **Mahesh Kerai.**  

**Made with ❤️ by Mahesh Kerai** 