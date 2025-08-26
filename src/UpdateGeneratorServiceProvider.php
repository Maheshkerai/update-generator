<?php

declare(strict_types=1);

namespace Mahesh\UpdateGenerator;

use Illuminate\Support\ServiceProvider;
use Mahesh\UpdateGenerator\Commands\GenerateUpdateCommand;
use Mahesh\UpdateGenerator\Services\FileService;
use Mahesh\UpdateGenerator\Services\GitService;
use Mahesh\UpdateGenerator\Services\UpdateGeneratorService;

final class UpdateGeneratorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/update-generator.php' => config_path('update-generator.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateUpdateCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/update-generator.php', 'update-generator');

        // Register services
        $this->app->singleton(GitService::class);
        $this->app->singleton(FileService::class);
        $this->app->singleton(UpdateGeneratorService::class, function ($app) {
            return new UpdateGeneratorService(
                $app->make(GitService::class),
                $app->make(FileService::class)
            );
        });

        // Register facade
        $this->app->alias(UpdateGeneratorService::class, 'update-generator');
    }
}
