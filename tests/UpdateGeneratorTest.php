<?php

declare(strict_types=1);

namespace Mahesh\UpdateGenerator\Tests;

use Mahesh\UpdateGenerator\Exceptions\GitException;
use Mahesh\UpdateGenerator\Exceptions\UpdateGeneratorException;
use Mahesh\UpdateGenerator\Helpers\UpdateHelper;
use Mahesh\UpdateGenerator\Services\FileService;
use Mahesh\UpdateGenerator\Services\GitService;
use Mahesh\UpdateGenerator\Services\UpdateGeneratorService;
use PHPUnit\Framework\TestCase;

class UpdateGeneratorTest extends TestCase
{
    private UpdateGeneratorService $updateGenerator;
    private GitService $gitService;
    private FileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->gitService = new GitService();
        $this->fileService = new FileService();
        $this->updateGenerator = new UpdateGeneratorService($this->gitService, $this->fileService);
    }

    public function testVersionValidation(): void
    {
        $this->expectException(UpdateGeneratorException::class);
        $this->expectExceptionMessage('Invalid version format. Use format like 1.0.0');
        
        $this->updateGenerator->generateNewInstallation('invalid-version');
    }

    public function testSameVersionValidation(): void
    {
        $this->expectException(UpdateGeneratorException::class);
        $this->expectExceptionMessage('Current version and update version cannot be the same');
        
        $this->updateGenerator->generateUpdate('2025-01-01', '2025-03-31', '1.0.0', '1.0.0');
    }

    public function testHelperClassExists(): void
    {
        $this->assertTrue(class_exists(UpdateHelper::class));
        $this->assertTrue(method_exists(UpdateHelper::class, 'prepareUpdateFiles'));
        $this->assertTrue(method_exists(UpdateHelper::class, 'prepareNewInstallationFiles'));
    }

    public function testServiceClassesExist(): void
    {
        $this->assertTrue(class_exists(GitService::class));
        $this->assertTrue(class_exists(FileService::class));
        $this->assertTrue(class_exists(UpdateGeneratorService::class));
    }

    public function testExceptionClassesExist(): void
    {
        $this->assertTrue(class_exists(GitException::class));
        $this->assertTrue(class_exists(UpdateGeneratorException::class));
    }
} 