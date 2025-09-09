# Update Generator

A robust Laravel package for generating update ZIP files and new installation packages based on Git repository changes.

## Features

- 🚀 **Git Integration**: Automatically detects files changed between specified dates
- 📦 **Multiple Package Types**: Generate update packages, new installation packages, or both
- 🛡️ **Security**: Safe Git command execution with proper validation
- 🔒 **Environment Sanitization**: Automatically sanitizes sensitive data in .env files for new installations
- 🧹 **Cache Management**: Automatic cache clearing before package generation
- 📝 **Logging**: Comprehensive logging for debugging and monitoring
- ⚙️ **Configurable**: Easy configuration for excluded paths and settings
- 🎯 **Type Safety**: Full PHP 8.1+ type safety with strict typing
- 📁 **Custom File Inclusion**: Explicitly include custom packages and essential files in updates
- 🗂️ **Smart Exclusions**: Wildcard patterns for excluding directory contents while preserving structure

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
    
    /*
    |--------------------------------------------------------------------------
    | Exclude paths for new installation packages
    |--------------------------------------------------------------------------
    |
    | These paths will be excluded when generating new installation packages
    | Note: .env file is included for fresh installations as it's required
    | Use wildcard patterns (*) to exclude contents while preserving directories
    |
    */
    'exclude_new' => [
        'storage/app/public',
        'storage/logs/*',           // Excludes log files, keeps directory
        'storage/framework/cache/data', // Excludes cache data, keeps directory
        'storage/framework/sessions/*', // Excludes session files, keeps directory
        'storage/framework/views/*',    // Excludes compiled views, keeps directory
        'storage/debugbar/*',           // Excludes debugbar files, keeps directory
        '.git',
        '.idea',
        'node_modules',
        'public/storage',
        'public/uploads',
        '.vscode',
        'storage/installed',
    ],
    
    'output_directory' => 'storage/app/update_files',
    'git_timeout' => 300,
    'enable_logging' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Clear cache before generation
    |--------------------------------------------------------------------------
    |
    | Whether to clear all cache files before generating packages
    | This ensures no cached data is included in the packages
    |
    */
    'clear_cache_before_generation' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Sanitize .env file
    |--------------------------------------------------------------------------
    |
    | Whether to sanitize the .env file by replacing sensitive values
    | with default values or null before generating packages
    |
    */
    'sanitize_env_file' => true,
    
    /*
    |--------------------------------------------------------------------------
    | .env file sanitization rules
    |--------------------------------------------------------------------------
    |
    | Define which environment variables should be sanitized and their
    | replacement values for new installation packages
    |
    */
    'env_sanitization_rules' => [
        'APP_KEY' => 'base64:your-app-key-here',
        'DB_PASSWORD' => '',
        'DB_USERNAME' => 'root',
        'DB_DATABASE' => 'laravel',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'MAIL_PASSWORD' => '',
        'MAIL_USERNAME' => '',
        'MAIL_HOST' => 'smtp.mailgun.org',
        'MAIL_PORT' => '587',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_FROM_ADDRESS' => 'hello@example.com',
        'MAIL_FROM_NAME' => 'Laravel',
        'PUSHER_APP_KEY' => '',
        'PUSHER_APP_SECRET' => '',
        'PUSHER_APP_ID' => '',
        'PUSHER_APP_CLUSTER' => 'mt1',
        'MIX_PUSHER_APP_KEY' => '',
        'MIX_PUSHER_APP_CLUSTER' => 'mt1',
        'AWS_ACCESS_KEY_ID' => '',
        'AWS_SECRET_ACCESS_KEY' => '',
        'AWS_DEFAULT_REGION' => 'us-east-1',
        'AWS_BUCKET' => '',
        'REDIS_PASSWORD' => '',
        'REDIS_HOST' => '127.0.0.1',
        'REDIS_PORT' => '6379',
        'CACHE_DRIVER' => 'file',
        'SESSION_DRIVER' => 'file',
    ],
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

## Quick Start Guide

### 🚀 Generate Your First Package

1. **Basic Update Package:**
```bash
php artisan update:generate \
    --start_date=2025-01-01 \
    --end_date=2025-03-31 \
    --current_version=1.0.0 \
    --update_version=1.1.0 \
    --type=update
```

2. **New Installation Package:**
```bash
php artisan update:generate \
    --update_version=1.1.0 \
    --type=new
```

3. **Both Packages:**
```bash
php artisan update:generate \
    --start_date=2025-01-01 \
    --end_date=2025-03-31 \
    --current_version=1.0.0 \
    --update_version=1.1.0 \
    --type=both
```

### 🔒 Security Features (Automatic)

- ✅ **Environment sanitization** - Sensitive data automatically removed
- ✅ **Cache clearing** - No cached data in packages
- ✅ **Smart exclusions** - Essential directories preserved
- ✅ **Clean packages** - Production-ready installations

### ⚙️ Customization

Edit `config/update-generator.php` to:
- Add custom files to include: `'add_update_file' => [...]`
- Configure exclusions: `'exclude_new' => [...]`
- Customize sanitization: `'env_sanitization_rules' => [...]`
- Control cache clearing: `'clear_cache_before_generation' => true`

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
| `clear_cache_before_generation` | Clear cache before generating packages | `true` |
| `sanitize_env_file` | Sanitize .env file for new installations | `true` |
| `env_sanitization_rules` | Rules for sanitizing environment variables | See config |

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

### Environment File Sanitization

The package automatically sanitizes sensitive data in `.env` files when generating new installation packages. This ensures that:

- **Sensitive credentials** are removed or replaced with safe defaults
- **API keys and passwords** are cleared
- **Database credentials** are reset to default values
- **A new APP_KEY** is generated for each installation
- **Mail settings** are reset to safe defaults

**Before Sanitization:**
```env
DB_PASSWORD=my_secret_password
MAIL_PASSWORD=my_email_password
APP_KEY=base64:actual_encryption_key
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
```

**After Sanitization:**
```env
DB_PASSWORD=
MAIL_PASSWORD=
APP_KEY=base64:new_generated_key_here
AWS_ACCESS_KEY_ID=
```

### Wildcard Exclusion Patterns

Use wildcard patterns (`*`) to exclude directory contents while preserving the directory structure:

```php
'exclude_new' => [
    'storage/logs/*',           // Excludes all files in logs directory
    'storage/framework/sessions/*', // Excludes session files, keeps directory
    'storage/debugbar/*',       // Excludes debugbar files, keeps directory
],
```

This ensures that:
- ✅ **Directory structure is preserved** - Laravel can create files in these directories
- ✅ **Contents are excluded** - No sensitive or temporary files are included
- ✅ **No runtime errors** - Essential directories exist for the application to function

### Cache Management

The package automatically clears various cache types before generating packages:

- **Application cache** (`storage/framework/cache`)
- **View cache** (`storage/framework/views`)
- **Session files** (`storage/framework/sessions`)
- **Bootstrap cache** (`bootstrap/cache`)
- **Laravel Artisan caches** (`cache:clear`, `view:clear`, `config:clear`, `route:clear`)

This ensures that no cached data is included in your packages, keeping them clean and up-to-date.


## Best Practices

1. **Version Naming**: Use semantic versioning (e.g., 1.0.0, 1.1.0)
2. **Date Format**: Always use YYYY-MM-DD format for dates
3. **Git Repository**: Ensure you're in a Git repository before running commands
4. **Exclusions**: Configure appropriate exclusions for your project
5. **Custom Files**: Use `add_update_file` to include essential custom packages and dependencies
6. **Testing**: Test generated packages in a staging environment before production use
7. **Environment Security**: Always enable `.env` sanitization for production packages
8. **Wildcard Patterns**: Use wildcard patterns (`*`) to exclude directory contents while preserving structure
9. **Cache Clearing**: Keep `clear_cache_before_generation` enabled to ensure clean packages
10. **Sanitization Rules**: Customize `env_sanitization_rules` to match your application's needs

## Recent Updates

### 🔒 Security Enhancements
- **Environment File Sanitization**: Automatically removes sensitive data from `.env` files in new installation packages
- **Smart Exclusion Patterns**: Wildcard patterns (`*`) for excluding directory contents while preserving structure
- **Cache Management**: Automatic cache clearing before package generation

### 🛠️ Technical Improvements
- **ZIP Creation**: Replaced PHP ZipArchive with system `zip` command for better reliability
- **Infinite Loop Prevention**: Fixed recursive copying issues in new installation generation
- **File System Safety**: Enhanced file copying with proper path validation and error handling
- **Logging Enhancement**: Improved logging with masked sensitive values for security

### 📦 Package Generation
- **Essential Files**: Automatic inclusion of `vendor/autoload.php`, `vendor/composer`, and custom packages
- **Directory Structure**: Preserved essential Laravel directories (`storage/framework/sessions`, etc.)
- **Clean Packages**: No cached data or temporary files included in packages

### ⚙️ Configuration Options
- **`sanitize_env_file`**: Enable/disable `.env` file sanitization
- **`env_sanitization_rules`**: Customize which environment variables to sanitize
- **`clear_cache_before_generation`**: Control automatic cache clearing
- **Wildcard Exclusions**: Use `storage/logs/*` patterns for smart exclusions

## Troubleshooting

### Common Issues

**Q: Getting "Failed to create ZIP archive" error?**
A: This has been fixed by switching to system `zip` command. Ensure `zip` utility is installed on your system.

**Q: New installation packages missing essential directories?**
A: Use wildcard patterns like `storage/logs/*` instead of `storage/logs` to preserve directory structure.

**Q: Sensitive data in .env files?**
A: Enable `sanitize_env_file` and configure `env_sanitization_rules` to automatically clean sensitive data.

**Q: Package generation in infinite loop?**
A: This has been fixed by using system temp directory and proper path validation.

**Q: Missing vendor files in new installations?**
A: The package now automatically includes essential vendor files like `vendor/autoload.php` and `vendor/composer`.

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