<?php

declare(strict_types=1);

namespace Mahesh\UpdateGenerator\Commands;

use Illuminate\Console\Command;
use Mahesh\UpdateGenerator\Exceptions\GitException;
use Mahesh\UpdateGenerator\Exceptions\UpdateGeneratorException;
use Mahesh\UpdateGenerator\Services\UpdateGeneratorService;

use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

final class GenerateUpdateCommand extends Command
{
    protected $signature = 'update:generate 
                            {--start_date= : Start date (YYYY-MM-DD)} 
                            {--end_date= : End date (YYYY-MM-DD)} 
                            {--start_commit= : Starting commit reference (SHA, branch, tag)}
                            {--end_commit= : Ending commit reference (SHA, branch, tag)}
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
            $startCommit = $this->option('start_commit');
            $endCommit = $this->option('end_commit');
            $currentVersion = $this->option('current_version');
            $updateVersion = $this->option('update_version');
            $type = $this->option('type');

            $prompted = false;
            $mode = 'date'; // default mode

            // Determine mode if not explicitly provided via options
            if (!$startDate && !$endDate && ($startCommit || $endCommit)) {
                $mode = 'commit';
            }

            // Step 1: Mode Selection (Only if not provided via options)
            if (!$startDate && !$endDate && !$startCommit && !$endCommit) {
                $modeChoice = select(
                    label: 'Select filtering method',
                    options: [
                        'date' => 'Date Range (Default)',
                        'commit' => 'Commit Range',
                    ],
                    default: 'date'
                );
                $mode = $modeChoice;
                $prompted = true;
            }

            // Step 2 & 3: Conditional Inputs + Sequence
            if ($type !== 'new') {
                if ($mode === 'date') {
                    if (!$startDate) {
                        $startDate = $this->promptForDate('Enter start date');
                        $prompted = true;
                    }
                    if (!$endDate) {
                        $endDate = $this->promptForDate('Enter end date');
                        $prompted = true;
                    }
                } else {
                    if (!$startCommit) {
                        $startCommit = text(
                            label: 'Enter the starting commit reference',
                            placeholder: 'SHA',
                            hint: 'Supports SHA (full/short)',
                            required: true
                        );
                        $prompted = true;
                    }
                    if (!$endCommit) {
                        $endCommit = text(
                            label: 'Enter the ending commit reference',
                            placeholder: 'SHA, branch, or tag',
                            hint: 'Example: a1b2c3d, main, v1.0.0',
                            required: true
                        );
                        $prompted = true;
                    }
                }

                // Current Version
                if (!$currentVersion) {
                    $currentVersion = $this->promptForVersion('Enter current system version');
                    $prompted = true;
                }
            }

            // Update Version
            if (!$updateVersion) {
                $updateVersion = $this->promptForVersion('Enter update version');
                $prompted = true;
            }

            // Type Selection
            if (!$type) {
                $type = $this->promptForType('Select package type');
                $prompted = true;
            }

            // Summary confirmation for interactive flow
            if ($prompted) {
                $this->newLine();
                info('Confirmation Summary:');
                $this->line("Mode: " . ($mode === 'date' ? 'Date Range' : 'Commit Range'));

                if (in_array($type, ['update', 'both'])) {
                    if ($mode === 'date') {
                        $this->line("Start Date: {$startDate}");
                        $this->line("End Date: {$endDate}");
                    } else {
                        $this->line("Start Commit: {$startCommit}");
                        $this->line("End Commit: {$endCommit}");
                    }
                    $this->line("Current Version: {$currentVersion}");
                }
                $this->line("Update Version: {$updateVersion}");
                $this->line("Type: {$type}");

                if (!confirm('Proceed with generation?', true)) {
                    warning('Operation cancelled by user.');
                    return self::SUCCESS;
                }
            }

            $this->validateData($type, $startDate, $endDate, $startCommit, $endCommit, $currentVersion, $updateVersion, $mode);

            info('🚀 Starting package generation...');

            $generatedFiles = match ($type) {
                'update' => $mode === 'date'
                    ? $this->updateGeneratorService->generateUpdate($startDate, $endDate, $currentVersion, $updateVersion)
                    : $this->updateGeneratorService->generateUpdateByCommitRange($startCommit, $endCommit, $currentVersion, $updateVersion),
                'new' => $this->updateGeneratorService->generateNewInstallation($updateVersion),
                'both' => $mode === 'date'
                    ? $this->updateGeneratorService->generateBoth($startDate, $endDate, $currentVersion, $updateVersion)
                    : $this->updateGeneratorService->generateBothByCommitRange($startCommit, $endCommit, $currentVersion, $updateVersion),
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
        return text(
            label: $question,
            placeholder: 'YYYY-MM-DD',
            hint: 'Enter date in YYYY-MM-DD format (Ensure dates are within repository activity range)',
            validate: fn(string $value) => $this->isValidDate($value)
                ? null
                : 'Invalid date format. Please use YYYY-MM-DD'
        );
    }

    private function promptForVersion(string $question): string
    {
        return text(
            label: $question,
            placeholder: 'e.g. 1.0.0',
            validate: fn(string $value) => !empty(trim($value))
                ? null
                : 'Version cannot be empty'
        );
    }

    private function promptForType(string $question): string
    {
        return select(
            label: $question,
            options: [
                'both' => 'Both',
                'update' => 'Update',
                'new' => 'New',
            ],
            default: 'both'
        );
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
    private function validateData(
        ?string $type,
        ?string $startDate,
        ?string $endDate,
        ?string $startCommit,
        ?string $endCommit,
        ?string $currentVersion,
        ?string $updateVersion,
        string $mode = 'date'
    ): void {
        if (!in_array($type, ['update', 'new', 'both'])) {
            throw new UpdateGeneratorException("Invalid type: {$type}. Use 'update', 'new', or 'both'");
        }

        if (in_array($type, ['update', 'both'])) {
            if ($mode === 'date') {
                if (!$startDate) {
                    throw new UpdateGeneratorException('Start date is required for update packages in date mode');
                }
                if (!$this->isValidDate($startDate)) {
                    throw new UpdateGeneratorException('Invalid start date format. Please use YYYY-MM-DD');
                }

                if (!$endDate) {
                    throw new UpdateGeneratorException('End date is required for update packages in date mode');
                }
                if (!$this->isValidDate($endDate)) {
                    throw new UpdateGeneratorException('Invalid end date format. Please use YYYY-MM-DD');
                }
            } else {
                if (!$startCommit) {
                    throw new UpdateGeneratorException('Starting commit reference is required for update packages in commit mode');
                }
                if (!$endCommit) {
                    throw new UpdateGeneratorException('Ending commit reference is required for update packages in commit mode');
                }
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
