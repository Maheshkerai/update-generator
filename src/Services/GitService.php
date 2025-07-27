<?php

declare(strict_types=1);

namespace Mahesh\UpdateGenerator\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mahesh\UpdateGenerator\Exceptions\GitException;

final class GitService
{
    private const GIT_COMMAND_TIMEOUT = 300;

    /**
     * Get changed files between two dates
     *
     * @param string $startDate
     * @param string $endDate
     * @return array<string>
     * @throws GitException
     */
    public function getChangedFiles(string $startDate, string $endDate): array
    {
        $this->validateDates($startDate, $endDate);
        $this->ensureGitRepository();

        $startCommit = $this->getCommitBeforeDate($startDate);
        $endCommit = $this->getCommitBeforeDate($endDate);

        if (!$startCommit || !$endCommit) {
            throw new GitException('Unable to find commits for the specified dates');
        }

        $command = "git diff --name-only --diff-filter=ACM {$startCommit} {$endCommit}";
        $output = $this->executeGitCommand($command);

        if ($output === null) {
            throw new GitException('Failed to get changed files from Git');
        }

        $files = array_filter(explode("\n", trim($output)));
        
        if (config('update-generator.enable_logging', true)) {
            Log::info('Git changed files retrieved', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'file_count' => count($files),
                'files' => $files
            ]);
        }

        return $files;
    }

    /**
     * Validate date format and logic
     *
     * @param string $startDate
     * @param string $endDate
     * @throws GitException
     */
    private function validateDates(string $startDate, string $endDate): void
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } catch (\Exception $e) {
            throw new GitException('Invalid date format. Use YYYY-MM-DD format');
        }

        if ($start->isAfter($end)) {
            throw new GitException('Start date cannot be after end date');
        }
    }

    /**
     * Ensure we're in a Git repository
     *
     * @throws GitException
     */
    private function ensureGitRepository(): void
    {
        $command = 'git rev-parse --git-dir';
        $output = $this->executeGitCommand($command);

        if ($output === null) {
            throw new GitException('Not a Git repository or Git is not installed');
        }
    }

    /**
     * Get commit hash before a specific date
     *
     * @param string $date
     * @return string|null
     */
    private function getCommitBeforeDate(string $date): ?string
    {
        $command = "git rev-list -n 1 --before=\"{$date}\" HEAD";
        $output = $this->executeGitCommand($command);

        return $output ? trim($output) : null;
    }

    /**
     * Execute Git command safely
     *
     * @param string $command
     * @return string|null
     */
    private function executeGitCommand(string $command): ?string
    {
        $timeout = config('update-generator.git_timeout', self::GIT_COMMAND_TIMEOUT);
        
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            return null;
        }

        // Set timeout
        stream_set_timeout($pipes[1], $timeout);
        stream_set_timeout($pipes[2], $timeout);

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            if (config('update-generator.enable_logging', true)) {
                Log::error('Git command failed', [
                    'command' => $command,
                    'error' => $error,
                    'return_code' => $returnCode
                ]);
            }
            return null;
        }

        return $output;
    }
} 