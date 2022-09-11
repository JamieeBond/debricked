<?php

namespace App\Tests\CQRS\Command;

use App\CQRS\Command\UploadCommand;
use PHPUnit\Framework\TestCase;

class UploadCommandTest extends TestCase
{
    public function testConstructor(): void
    {
        $repositoryName = 'repo-1';
        $commitName = 'commit-1';
        $files = ['text.txt'];

        $command = new UploadCommand(
            $repositoryName,
            $commitName,
            $files
        );

        $this->assertSame($repositoryName, $command->getRepositoryName());
        $this->assertSame($commitName, $command->getCommitName());
        $this->assertSame($files, $command->getFiles());
    }
}