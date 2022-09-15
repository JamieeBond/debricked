<?php

namespace App\Tests\Command;

use App\Command\RunScanCommand;
use App\Entity\Upload;
use App\Factory\TriggerFactory;
use App\Messenger\ScanMessenger;
use App\Model\StatusResponse;
use App\Model\Trigger;
use App\Repository\UploadRepository;
use App\Util\DebrickedApiUtil;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RunScanCommandTest extends TestCase
{
    private const SUCCESSFUL_MSG = 'Finished.';

    /**
     * @var MockObject|EntityManager|null
     */
    private MockObject|EntityManager|null $em = null;

    /**
     * @var MockObject|UploadRepository|null
     */
    private MockObject|UploadRepository|null $repo = null;

    /**
     * @var MockObject|DebrickedApiUtil|null
     */
    private MockObject|DebrickedApiUtil|null $apiUtil = null;

    /**
     * @var MockObject|SerializerInterface|null
     */
    private MockObject|SerializerInterface|null $serializer = null;

    /**
     * @var MockObject|TriggerFactory|null
     */
    private MockObject|TriggerFactory|null $triggerFactory = null;

    /**
     * @var MockObject|ScanMessenger|null
     */
    private MockObject|ScanMessenger|null $scanMessenger = null;

    /**
     * @var MockObject|CommandTester|null
     */
    private MockObject|CommandTester|null $commandTester = null;

    protected function setUp(): void
    {
        $this->em = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo = $this
            ->getMockBuilder(UploadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->em
            ->method('getRepository')
            ->with(Upload::class)
            ->willReturn($this->repo)
        ;

        $this->apiUtil = $this
            ->getMockBuilder(DebrickedApiUtil::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this
            ->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->triggerFactory = $this
            ->getMockBuilder(TriggerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scanMessenger = $this
            ->getMockBuilder(ScanMessenger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new RunScanCommand(
            $this->em,
            $this->apiUtil,
            $this->serializer,
            $this->triggerFactory,
            $this->scanMessenger
        ));

        $command = $application->find('app:run-scan');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->em = null;
        $this->repo = null;
        $this->apiUtil = null;
        $this->serializer = null;
        $this->triggerFactory = null;
        $this->scanMessenger = null;
        $this->commandTester = null;
    }

    private function createUpload(string $ciUploadId): Upload
    {
        $upload = $this
            ->getMockBuilder(Upload::class)
            ->disableOriginalConstructor()
            ->getMock();

        $upload
            ->expects($this->atLeastOnce())
            ->method('getCiUploadId')
            ->willReturn($ciUploadId)
        ;

        return $upload;
    }

    private function createResponse(int $status, ?string $content = null): ResponseInterface
    {
        $response = $this
            ->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn($status)
        ;

        if (null !== $content) {
            $response
                ->expects($this->atLeastOnce())
                ->method('getContent')
                ->willReturn($content)
            ;
        }

        return $response;
    }

    private function createStatusResponse(int $vFound, float $maxCvss, string $url): StatusResponse
    {
        $statusResponse = $this
            ->getMockBuilder(StatusResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statusResponse
            ->expects($this->atLeastOnce())
            ->method('getVulnerabilitiesFound')
            ->willReturn($vFound)
        ;

        $statusResponse
            ->expects($this->atLeastOnce())
            ->method('getMaxCvss')
            ->willReturn($maxCvss)
        ;

        $statusResponse
            ->expects($this->atLeastOnce())
            ->method('getDetailsUrl')
            ->willReturn($url)
        ;

        return $statusResponse;
    }

    public function testExecuteNoUploads(): void
    {
        $this->repo
            ->expects($this->once())
            ->method('findAllUnscanned')
            ->willReturn([])
        ;

        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful(self::SUCCESSFUL_MSG);
    }

    public function testExecuteWithOptions(): void
    {
        // First Upload
        $ciUploadId1 = 'ci1';
        $content1 = '{c1}';
        $vul1 = 10;
        $cvss1 = 6.0;
        $url1 = 'www.de.com/c1';
        $upload1 = $this->createUpload($ciUploadId1);
        $upload1
            ->expects($this->once())
            ->method('setStatus')
            ->with(Response::HTTP_OK)
        ;
        $response1 = $this->createResponse(
            Response::HTTP_OK,
            $content1
        );
        $statusResponse1 = $this->createStatusResponse(
            $vul1,
            $cvss1,
            $url1
        );

        // Second Upload
        $ciUploadId2 = 'ci2';
        $content2 = '{c2}';
        $vul2 = 5;
        $cvss2 = 2.0;
        $url2 = 'www.de.com/c2';
        $upload2 = $this->createUpload($ciUploadId2);
        $upload2
            ->expects($this->once())
            ->method('setStatus')
            ->with(Response::HTTP_OK)
        ;
        $response2 = $this->createResponse(
            Response::HTTP_OK,
            $content2
        );
        $statusResponse2 = $this->createStatusResponse(
            5,
            2,
            $url2
        );

        $this->repo
            ->expects($this->once())
            ->method('findAllUnscanned')
            ->willReturn([
                $upload1,
                $upload2,
            ])
        ;

        $this->apiUtil
            ->expects($this->exactly(2))
            ->method('currentFileStatus')
            ->withConsecutive(
                [$ciUploadId1,],
                [$ciUploadId2,]
            )
            ->willReturnOnConsecutiveCalls(
                $response1,
                $response2
            )
        ;

        $this->serializer
            ->expects($this->exactly(2))
            ->method('deserialize')
            ->withConsecutive(
                [$content1, StatusResponse::class, 'json',],
                [$content2, StatusResponse::class, 'json',]
            )
            ->willReturnOnConsecutiveCalls(
                $statusResponse1,
                $statusResponse2
            )
        ;

        $this->triggerFactory
            ->expects($this->exactly(6))
            ->method('create')
            ->withConsecutive(
                [Trigger::SCAN_IS_COMPLETE,],
                [Trigger::VULNERABILITIES_GREATER_THAN, 1, $vul1],
                [Trigger::CVSS_GREATER_THAN, 1, $cvss1,],
                [Trigger::SCAN_IS_COMPLETE,],
                [Trigger::VULNERABILITIES_GREATER_THAN, 1, $vul2],
                [Trigger::CVSS_GREATER_THAN, 1, $cvss2],
            )
        ;

        $this->scanMessenger
            ->expects($this->exactly(2))
            ->method('sendTriggeredEmail')
            ->withConsecutive(
                [$upload1, $this->isType(IsType::TYPE_ARRAY), $url1,],
                [$upload2, $this->isType(IsType::TYPE_ARRAY), $url2,]
            )
        ;

        $this->commandTester->execute(
            [
                '--'.RunScanCommand::ACTION_EMAIL => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_IN_PROGRESS => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_IS_COMPLETE => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_VUL_GREATER_THAN => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_CVSS_GREATER_THAN => 1,
            ]
        );

        $this->commandTester->assertCommandIsSuccessful(self::SUCCESSFUL_MSG);
    }

    public function testExecuteWithOptionsOnlyOneTriggered(): void
    {
        // First Upload
        $ciUploadId1 = 'ci1';
        $content1 = '{c1}';
        $vul1 = 10;
        $cvss1 = 6.0;
        $url1 = 'www.de.com/c1';
        $upload1 = $this->createUpload($ciUploadId1);
        $upload1
            ->expects($this->once())
            ->method('setStatus')
            ->with(Response::HTTP_OK)
        ;
        $response1 = $this->createResponse(
            Response::HTTP_OK,
            $content1
        );
        $statusResponse1 = $this->createStatusResponse(
            $vul1,
            $cvss1,
            $url1
        );

        // Second Upload
        $ciUploadId2 = 'ci2';
        $content2 = '{c2}';
        $url2 = 'www.de.com/c2';
        $upload2 = $this->createUpload($ciUploadId2);
        $upload2
            ->expects($this->once())
            ->method('setStatus')
            ->with(Response::HTTP_OK)
        ;
        $response2 = $this->createResponse(
            Response::HTTP_OK,
            $content2
        );
        $statusResponse2 = $this->createStatusResponse(
            5,
            2,
            $url2
        );

        $this->repo
            ->expects($this->once())
            ->method('findAllUnscanned')
            ->willReturn([
                $upload1,
                $upload2,
            ])
        ;

        $this->apiUtil
            ->expects($this->exactly(2))
            ->method('currentFileStatus')
            ->withConsecutive(
                [$ciUploadId1,],
                [$ciUploadId2,]
            )
            ->willReturnOnConsecutiveCalls(
                $response1,
                $response2
            )
        ;

        $this->serializer
            ->expects($this->exactly(2))
            ->method('deserialize')
            ->withConsecutive(
                [$content1, StatusResponse::class, 'json',],
                [$content2, StatusResponse::class, 'json',]
            )
            ->willReturnOnConsecutiveCalls(
                $statusResponse1,
                $statusResponse2
            )
        ;

        $this->triggerFactory
            ->expects($this->once())
            ->method('create')
            ->with(Trigger::VULNERABILITIES_GREATER_THAN, 9, $vul1)
        ;

        $this->scanMessenger
            ->expects($this->once())
            ->method('sendTriggeredEmail')
            ->with($upload1, $this->isType(IsType::TYPE_ARRAY), $url1)
        ;

        $this->commandTester->execute(
            [
                '--'.RunScanCommand::ACTION_EMAIL => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_IN_PROGRESS => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_IS_COMPLETE => 0,
                '--'.RunScanCommand::OPTION_TRIGGER_VUL_GREATER_THAN => 9,
                '--'.RunScanCommand::OPTION_TRIGGER_CVSS_GREATER_THAN => 6,
            ]
        );

        $this->commandTester->assertCommandIsSuccessful(self::SUCCESSFUL_MSG);
    }

    public function testExecuteWithOptionsOneTriggeredOneInProgress(): void
    {
        // First Upload
        $ciUploadId1 = 'ci1';
        $content1 = '{c1}';
        $vul1 = 10;
        $cvss1 = 6.0;
        $url1 = 'www.de.com/c1';
        $upload1 = $this->createUpload($ciUploadId1);
        $upload1
            ->expects($this->once())
            ->method('setStatus')
            ->with(Response::HTTP_OK)
        ;
        $response1 = $this->createResponse(
            Response::HTTP_OK,
            $content1
        );
        $statusResponse1 = $this->createStatusResponse(
            $vul1,
            $cvss1,
            $url1
        );

        // Second Upload
        $ciUploadId2 = 'ci2';
        $upload2 = $this->createUpload($ciUploadId2);
        $upload2
            ->expects($this->once())
            ->method('setStatus')
            ->with(Response::HTTP_ACCEPTED)
        ;
        $response2 = $this->createResponse(
            Response::HTTP_ACCEPTED
        );

        $this->repo
            ->expects($this->once())
            ->method('findAllUnscanned')
            ->willReturn([
                $upload1,
                $upload2,
            ])
        ;

        $this->apiUtil
            ->expects($this->exactly(2))
            ->method('currentFileStatus')
            ->withConsecutive(
                [$ciUploadId1,],
                [$ciUploadId2,]
            )
            ->willReturnOnConsecutiveCalls(
                $response1,
                $response2
            )
        ;

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($content1, StatusResponse::class, 'json')
            ->willReturn($statusResponse1)
        ;

        $this->triggerFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [Trigger::SCAN_IS_COMPLETE,],
                [Trigger::VULNERABILITIES_GREATER_THAN, 9, $vul1]
            )
        ;

        $this->scanMessenger
            ->expects($this->once())
            ->method('sendTriggeredEmail')
            ->with($upload1, $this->isType(IsType::TYPE_ARRAY))
        ;

        $this->commandTester->execute(
            [
                '--'.RunScanCommand::ACTION_EMAIL => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_IN_PROGRESS => 0,
                '--'.RunScanCommand::OPTION_TRIGGER_IS_COMPLETE => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_VUL_GREATER_THAN => 9,
                '--'.RunScanCommand::OPTION_TRIGGER_CVSS_GREATER_THAN => 6,
            ]
        );

        $this->commandTester->assertCommandIsSuccessful(self::SUCCESSFUL_MSG);
    }

    public function testExecuteScanFailed(): void
    {
        // First Upload
        $ciUploadId1 = 'ci1';
        $upload1 = $this->createUpload($ciUploadId1);
        $response1 = $this->createResponse(
            Response::HTTP_BAD_REQUEST
        );

        // Second Upload
        $ciUploadId2 = 'ci2';
        $upload2 = $this->createUpload($ciUploadId2);
        $response2 = $this->createResponse(
            Response::HTTP_BAD_REQUEST
        );

        $this->repo
            ->expects($this->once())
            ->method('findAllUnscanned')
            ->willReturn([
                $upload1,
                $upload2,
            ])
        ;

        $this->apiUtil
            ->expects($this->exactly(2))
            ->method('currentFileStatus')
            ->withConsecutive(
                [$ciUploadId1,],
                [$ciUploadId2,]
            )
            ->willReturnOnConsecutiveCalls(
                $response1,
                $response2
            )
        ;

        $this->commandTester->execute(
            [
                '--'.RunScanCommand::ACTION_EMAIL => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_IN_PROGRESS => 0,
                '--'.RunScanCommand::OPTION_TRIGGER_IS_COMPLETE => 1,
                '--'.RunScanCommand::OPTION_TRIGGER_VUL_GREATER_THAN => 9,
                '--'.RunScanCommand::OPTION_TRIGGER_CVSS_GREATER_THAN => 6,
            ]
        );

        $this->assertSame(1, $this->commandTester->getStatusCode());
    }
}