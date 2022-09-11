<?php

namespace App\Tests\CQRS\Command;

use App\CQRS\Command\UploadCommand;
use App\CQRS\Command\UploadHandler;
use App\Entity\Upload;
use App\Util\DebrickedApiUtil;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHandlerTest extends TestCase
{
    /**
     * @var MockObject|EntityManagerInterface|null
     */
    private MockObject|EntityManagerInterface|null $em = null;

    /**
     * @var MockObject|DebrickedApiUtil|null
     */
    private MockObject|DebrickedApiUtil|null $apiUtil = null;

    protected function setUp(): void
    {
        $this->em = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->apiUtil = $this
            ->getMockBuilder(DebrickedApiUtil::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    private function createUploadedFile(string $name, string $path): UploadedFile
    {
        $uploadedFile = $this
            ->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $uploadedFile
            ->method('getClientOriginalName')
            ->willReturn($name)
        ;

        $uploadedFile
            ->method('getPathname')
            ->willReturn($path)
        ;

        return $uploadedFile;
    }

    public function testInvoke(): void
    {
        $em = $this->em;
        $apiUtil = $this->apiUtil;
        $ciUploadId = '23fefef';
        $repositoryName = 'repo-1';
        $commitName = 'commit-1';

        $nameOne = 'file.txt';
        $pathOne = 'er/ff';

        $fileOne = $this->createUploadedFile(
            $nameOne,
            $pathOne
        );

        $nameTwo = 'file2.txt';
        $pathTwo = 'er/gh';

        $fileTwo = $this->createUploadedFile(
            $nameTwo,
            $pathTwo
        );

        $apiUtil
            ->method('uploadDependencyFile')
            ->withConsecutive(
                [$repositoryName, $commitName, $pathOne, null,],
                [$repositoryName, $commitName, $pathTwo, $ciUploadId,],
            )
            ->willReturnOnConsecutiveCalls($ciUploadId, $ciUploadId)
        ;

        $apiUtil
            ->method('concludeFileUpload')
            ->with($ciUploadId)
        ;

        $em
            ->method('persist')
            ->with($this->isInstanceOf(Upload::class))
        ;

        $handler = new UploadHandler(
            $em,
            $apiUtil
        );

        $command = new UploadCommand(
            $repositoryName,
            $commitName,
            [$fileOne, $fileTwo],
        );

        $upload = $handler->__invoke($command);

        $this->assertSame($repositoryName, $upload->getRepositoryName());
        $this->assertSame($commitName, $upload->getCommitName());
        $this->assertSame($ciUploadId, $upload->getCiUploadId());
        $this->assertSame([$nameOne, $nameTwo,], $upload->getFiles());
    }
}