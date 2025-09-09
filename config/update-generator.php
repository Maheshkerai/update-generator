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
        '.vscode',
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
        'storage/app/public/*',
        'storage/logs/*',
        'storage/framework/cache/data',
        'storage/framework/sessions/*',
        'storage/framework/views/*',
        'storage/debugbar/*',
        '.git',
        '.idea',
        'node_modules',
        'public/storage',
        'public/uploads',
        '.vscode',
        'storage/installed',
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
        'APP_DEBUG' => 'false',
        'DEMO_MODE' => 'false',
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
    ],
];
