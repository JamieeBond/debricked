<?php

namespace App\Tests\Entity;

use App\Entity\Upload;
use PHPUnit\Framework\TestCase;
use Datetime;

class UploadTest extends TestCase
{
    private function createUpload(): Upload
    {
        return new Upload(
            '3445566',
            'repo-name',
            'commit-name',
            ['composer.json']
        );
    }

    public function testConstructor(): void
    {
        $ciUploadId = '8ejf7jskd';
        $repositoryName = 'repository-name-1';
        $commitName = 'commit-name-1';
        $files = [
            'symfony.lock',
            'composer.json',
        ];

        $upload = new Upload(
            $ciUploadId,
            $repositoryName,
            $commitName,
            $files
        );

        $this->assertNull($upload->getId());
        $this->assertInstanceOf(Datetime::class, $upload->getUploadedOn());
        $this->assertSame($ciUploadId, $upload->getCiUploadId());
        $this->assertSame($repositoryName, $upload->getRepositoryName());
        $this->assertSame($commitName, $upload->getCommitName());
        $this->assertSame($files, $upload->getFiles());
        $this->assertNull($upload->getStatus());
    }

    public function testSetGetStatus(): void
    {
        $upload = $this->createUpload();
        $value = 458;
        $upload->setStatus($value);
        $this->assertSame($value, $upload->getStatus());
    }
}
