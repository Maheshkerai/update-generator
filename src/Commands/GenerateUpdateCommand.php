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
                            {--type= : Type of package to generate (update, new, both)}';

    protected $description = 'Generate Laravel update and installation packages';

    public function __construct(
        private readonly UpdateGeneratorService $updateGeneratorService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $startDate = $this->option('start_date');
            $endDate = $this->option('end_date');
            $currentVersion = $this->option('current_version');
            $updateVersion = $this->option('update_version');
            $type = $this->option('type');

            $prompted = false;

            // Ask for inputs in the specified flow if missing
            if ($type !== 'new') {
                if (!$startDate) {
                    $startDate = $this->promptForDate('Enter start date (format: YYYY-MM-DD)');
                    $prompted = true;
                }
                if (!$endDate) {
                    $endDate = $this->promptForDate('Enter end date (format: YYYY-MM-DD)');
                    $prompted = true;
                }
                if (!$currentVersion) {
                    $currentVersion = $this->promptForVersion('Enter current system version');
                    $prompted = true;
                }
            }

            if (!$updateVersion) {
                $updateVersion = $this->promptForVersion('Enter update version');
                $prompted = true;
            }

            if (!$type) {
                $type = $this->promptForType('Enter type (both, update, new)');
                $prompted = true;
            }

            // Summary confirmation for interactive flow
            if ($prompted) {
                $this->newLine();
                $this->info('Confirm:');
                if (in_array($type, ['update', 'both'])) {
                    $this->line("Start Date: {$startDate}");
                    $this->line("End Date: {$endDate}");
                    $this->line("Current Version: {$currentVersion}");
                }
                $this->line("Update Version: {$updateVersion}");
                $this->line("Type: {$type}");
                
                if (!$this->confirm('Proceed? (yes/no)', true)) {
                    $this->info('Operation cancelled.');
                    return self::SUCCESS;
                }
            }

            $this->validateData($type, $startDate, $endDate, $currentVersion, $updateVersion);

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

    private function promptForDate(string $question): string
    {
        while (true) {
            $date = $this->ask($question);
            if ($this->isValidDate((string)$date)) {
                return (string)$date;
            }
            $this->error('Invalid date format. Please use YYYY-MM-DD');
        }
    }

    private function promptForVersion(string $question): string
    {
        while (true) {
            $version = $this->ask($question);
            if (!empty(trim((string)$version))) {
                return trim((string)$version);
            }
            $this->error('Version cannot be empty');
        }
    }

    private function promptForType(string $question): string
    {
        while (true) {
            $type = $this->ask($question, 'both');
            if (in_array((string)$type, ['update', 'new', 'both'], true)) {
                return (string)$type;
            }
            $this->error('Invalid type. Please use update, new, or both');
        }
    }

    private function isValidDate(?string $date): bool
    {
        if (empty($date)) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate command inputs
     *
     * @throws UpdateGeneratorException
     */
    private function validateData(?string $type, ?string $startDate, ?string $endDate, ?string $currentVersion, ?string $updateVersion): void
    {
        if (!in_array($type, ['update', 'new', 'both'])) {
            throw new UpdateGeneratorException("Invalid type: {$type}. Use 'update', 'new', or 'both'");
        }

        if (in_array($type, ['update', 'both'])) {
            if (!$startDate) {
                throw new UpdateGeneratorException('Start date is required for update packages');
            }
            if (!$this->isValidDate($startDate)) {
                throw new UpdateGeneratorException('Invalid start date format. Please use YYYY-MM-DD');
            }
            
            if (!$endDate) {
                throw new UpdateGeneratorException('End date is required for update packages');
            }
            if (!$this->isValidDate($endDate)) {
                throw new UpdateGeneratorException('Invalid end date format. Please use YYYY-MM-DD');
            }
            
            if (!$currentVersion) {
                throw new UpdateGeneratorException('Current version is required for update packages');
            }
        }

        if (!$updateVersion) {
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
