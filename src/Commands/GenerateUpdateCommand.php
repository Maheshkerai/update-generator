<?php

declare(strict_types=1);

namespace Mahesh\UpdateGenerator\Commands;

use Illuminate\Console\Command;
use Mahesh\UpdateGenerator\Exceptions\GitException;
use Mahesh\UpdateGenerator\Exceptions\UpdateGeneratorException;
use Mahesh\UpdateGenerator\Services\UpdateGeneratorService;

final class GenerateUpdateCommand extends Command
{
    protected $signature = 'update:generate 
                            {--start_date= : Start date (YYYY-MM-DD)} 
                            {--end_date= : End date (YYYY-MM-DD)} 
                            {--current_version= : Current version} 
                            {--update_version= : New version}
                            {--type=both : Type of package to generate (update, new, both)}';

    protected $description = 'Generate Laravel update and installation packages';

    public function __construct(
        private readonly UpdateGeneratorService $updateGeneratorService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->validateInputs();

            $startDate = $this->option('start_date');
            $endDate = $this->option('end_date');
            $currentVersion = $this->option('current_version');
            $updateVersion = $this->option('update_version');
            $type = $this->option('type');

            $this->info('🚀 Starting package generation...');

            $generatedFiles = match ($type) {
                'update' => $this->updateGeneratorService->generateUpdate($startDate, $endDate, $currentVersion, $updateVersion),
                'new' => $this->updateGeneratorService->generateNewInstallation($updateVersion),
                'both' => $this->updateGeneratorService->generateBoth($startDate, $endDate, $currentVersion, $updateVersion),
                default => throw new UpdateGeneratorException("Invalid type: {$type}. Use 'update', 'new', or 'both'")
            };

            $this->displayResults($generatedFiles, $type);

            return self::SUCCESS;

        } catch (GitException $e) {
            $this->error("❌ Git Error: {$e->getMessage()}");
            return self::FAILURE;

        } catch (UpdateGeneratorException $e) {
            $this->error("❌ Update Generator Error: {$e->getMessage()}");
            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error("❌ Unexpected Error: {$e->getMessage()}");
            if (config('app.debug')) {
                $this->error("Stack trace: {$e->getTraceAsString()}");
            }
            return self::FAILURE;
        }
    }

    /**
     * Validate command inputs
     *
     * @throws UpdateGeneratorException
     */
    private function validateInputs(): void
    {
        $type = $this->option('type');
        
        if (!in_array($type, ['update', 'new', 'both'])) {
            throw new UpdateGeneratorException("Invalid type: {$type}. Use 'update', 'new', or 'both'");
        }

        if (in_array($type, ['update', 'both'])) {
            if (!$this->option('start_date')) {
                throw new UpdateGeneratorException('Start date is required for update packages');
            }
            if (!$this->option('end_date')) {
                throw new UpdateGeneratorException('End date is required for update packages');
            }
            if (!$this->option('current_version')) {
                throw new UpdateGeneratorException('Current version is required for update packages');
            }
        }

        if (!$this->option('update_version')) {
            throw new UpdateGeneratorException('Update version is required');
        }
    }

    /**
     * Display generation results
     *
     * @param array<string> $generatedFiles
     * @param string $type
     */
    private function displayResults(array $generatedFiles, string $type): void
    {
        $this->newLine();
        $this->info('✅ Package generation completed successfully!');
        $this->newLine();

        $this->table(
            ['Type', 'Generated File'],
            array_map(fn($file) => [$type, basename($file)], $generatedFiles)
        );

        $this->newLine();
        $this->info('📁 Files saved to: ' . dirname($generatedFiles[0]));
    }
}
