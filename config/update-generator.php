<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Exclude paths for update packages
    |--------------------------------------------------------------------------
    |
    | These paths will be excluded when generating update packages
    |
    */
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
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Output directory
    |--------------------------------------------------------------------------
    |
    | Directory where generated packages will be stored
    |
    */
    'output_directory' => 'storage/app/update_files',

    /*
    |--------------------------------------------------------------------------
    | Git command timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for git commands
    |
    */
    'git_timeout' => 300,

    /*
    |--------------------------------------------------------------------------
    | Enable logging
    |--------------------------------------------------------------------------
    |
    | Whether to log update generation activities
    |
    */
    'enable_logging' => true,
];
