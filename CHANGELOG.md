# Changelog

All notable changes to the Update Generator package will be documented in this file.

## [2.0.0] - 2025-01-XX

### 🚀 Major Improvements

#### Architecture & Code Quality
- **Complete code refactoring** with modern PHP 8.1+ features
- **Strict typing** enabled throughout the codebase
- **SOLID principles** implementation with proper separation of concerns
- **Service-oriented architecture** with dedicated service classes
- **Final classes** to prevent inheritance and ensure data integrity
- **Comprehensive error handling** with custom exception classes

#### New Features
- **Multiple package types**: Generate update packages, new installation packages, or both
- **Enhanced command options**: Added `--type` parameter for flexible package generation
- **Comprehensive logging**: Detailed logging for debugging and monitoring
- **Configurable timeouts**: Git command timeout configuration
- **Better validation**: Input validation for dates, versions, and parameters
- **Facade support**: Clean API through Laravel facades

#### Security Improvements
- **Safe Git execution**: Replaced `shell_exec()` with secure `proc_open()`
- **Input validation**: Proper validation of all user inputs
- **Error isolation**: Better error handling without exposing sensitive information

#### Configuration Enhancements
- **Expanded exclusion lists**: More comprehensive default exclusions
- **Configurable output directory**: Customizable output location
- **Logging configuration**: Enable/disable logging as needed
- **Git timeout settings**: Configurable timeout for Git operations

### 🔧 Technical Changes

#### Service Classes
- **GitService**: Handles all Git-related operations with proper error handling
- **FileService**: Manages file operations, copying, and ZIP creation
- **UpdateGeneratorService**: Orchestrates the entire update generation process

#### Exception Handling
- **GitException**: For Git-related errors (not a repo, Git not installed, etc.)
- **UpdateGeneratorException**: For package-specific errors (invalid versions, no files, etc.)

#### Helper Class
- **Backward compatibility**: Maintains the original API while using new services
- **Static methods**: `prepareUpdateFiles()`, `prepareNewInstallationFiles()`, `prepareBothPackages()`
- **Legacy support**: Deprecated methods still work but recommend using new static methods

#### Command Improvements
- **Better validation**: Comprehensive input validation
- **Enhanced output**: Improved console output with tables and progress indicators
- **Error handling**: Proper error messages and exit codes
- **Type parameter**: Choose between 'update', 'new', or 'both' package types

### 📚 Documentation
- **Comprehensive README**: Complete documentation with examples
- **Usage examples**: Multiple ways to use the package
- **Configuration guide**: Detailed configuration options
- **Troubleshooting**: Common issues and solutions
- **Best practices**: Recommended usage patterns

### 🧪 Testing
- **Basic test structure**: PHPUnit test examples
- **Validation tests**: Tests for input validation
- **Error handling tests**: Tests for exception scenarios

### 🔄 Backward Compatibility
- **Helper class**: Original `UpdateHelper` methods still work
- **Configuration**: Existing config files will work with new defaults
- **Command**: Original command signature still supported

### 📦 Dependencies
- **PHP 8.1+**: Updated minimum PHP requirement
- **Laravel 9.x+**: Support for Laravel 9, 10, and 11
- **Additional Laravel packages**: Added console and filesystem dependencies

## [1.0.0] - 2024-XX-XX

### Initial Release
- Basic update package generation
- Git integration for file detection
- Simple command-line interface
- Basic configuration options
- ZIP file creation
- Version information files

---

## Migration Guide

### From 1.x to 2.0

1. **Update PHP requirement**: Ensure PHP 8.1+ is available
2. **Update composer.json**: The package will automatically update dependencies
3. **Publish new config**: Run `php artisan vendor:publish --tag=config` to get new configuration options
4. **Update usage**: Consider using the new static methods for better type safety

### Code Changes

#### Old Usage (Still Works)
```php
$helper = new UpdateHelper();
$helper->generateUpdate('2025-01-01', '2025-03-31', '1.0.0', '1.1.0');
$helper->generateNewInstallation('1.1.0');
```

#### New Recommended Usage
```php
// Using static methods
$updateFiles = UpdateHelper::prepareUpdateFiles('1.0.0', '1.1.0', '2025-01-01', '2025-03-31');
$installationFiles = UpdateHelper::prepareNewInstallationFiles('1.1.0');

// Using service classes
$updateGenerator = new UpdateGeneratorService(new GitService(), new FileService());
$files = $updateGenerator->generateBoth('2025-01-01', '2025-03-31', '1.0.0', '1.1.0');
```

### Configuration Changes

The configuration file has been expanded with new options. Existing configurations will work with new defaults, but you may want to review and update:

```php
// New options available
'output_directory' => 'storage/app/update_files',
'git_timeout' => 300,
'enable_logging' => true,
```

---

## Support

For questions about migration or new features, please refer to the README.md file or create an issue on GitHub. 