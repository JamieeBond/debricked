<?php

namespace App\Tests\Util;

use App\HttpClient\AuthenticatedHttpClient;
use App\HttpClient\BadResponseException;
use App\Util\DebrickedApiUtil;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DebrickedApiUtilTest extends TestCase
{
    /**
     * @var MockObject|AuthenticatedHttpClient|null
     */
    private MockObject|AuthenticatedHttpClient|null $client = null;

    /**
     * @var MockObject|ResponseInterface|null
     */
    private MockObject|ResponseInterface|null $response  = null;

    protected function setUp(): void
    {
        $this->client = $this
            ->getMockBuilder(AuthenticatedHttpClient::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->response = $this
            ->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    private function createUtil(AuthenticatedHttpClient $client): DebrickedApiUtil
    {
        return new DebrickedApiUtil($client);
    }

    public function testUploadDependencyFileSuccessful(): void
    {
        $client = $this->client;
        $response = $this->response;

        $ciUploadId = '3455673';

        $response
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $response
            ->expects($this->atLeastOnce())
            ->method('toArray')
            ->willReturn([
                'ciUploadId' => $ciUploadId,
            ])
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                Request::METHOD_POST,
                '/open/uploads/dependencies/files',
                $this->isType(IsType::TYPE_ARRAY)
            )
            ->willReturn($response);

        $util = $this->createUtil($client);

        $actual = $util->uploadDependencyFile(
            'repo-name-1',
            'commit-name-1',
            'composer.json'
        );

        $this->assertSame($ciUploadId, $actual);
    }

    public function testUploadDependencyFileException(): void
    {
        $this->expectException(BadResponseException::class);

        $client = $this->client;
        $response = $this->response;

        $response
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_BAD_REQUEST)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $util = $this->createUtil($client);

        $util->uploadDependencyFile(
            'repo-name-1',
            'commit-name-1',
            'composer.json'
        );
    }

    public function testConcludeFileUploadSuccessful(): void
    {
        $client = $this->client;
        $response = $this->response;

        $ciUploadId = '3455673';

        $response
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_NO_CONTENT)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                Request::METHOD_POST,
                '/open/finishes/dependencies/files/uploads',
                ['json' => ['ciUploadId' => $ciUploadId,]]
            )
            ->willReturn($response);

        $util = $this->createUtil($client);

        $actual = $util->concludeFileUpload(
            $ciUploadId
        );

        $this->assertSame($ciUploadId, $actual);
    }

    public function testConcludeFileUploadException(): void
    {
        $this->expectException(BadResponseException::class);

        $client = $this->client;
        $response = $this->response;

        $response
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_BAD_REQUEST)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $util = $this->createUtil($client);

        $util->concludeFileUpload(
            '334544'
        );
    }

    public function testCurrentFileStatus(): void
    {
        $client = $this->client;
        $response = $this->response;

        $ciUploadId = '3455673';

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                Request::METHOD_GET,
                '/open/ci/upload/status',
                ['query' => ['ciUploadId' => $ciUploadId,]]
            )
            ->willReturn($response);

        $util = $this->createUtil($client);

        $actual = $util->currentFileStatus(
            $ciUploadId
        );

        $this->assertInstanceOf(ResponseInterface::class, $actual);
    }

    public function testGetSupportedFormatsSuccessful(): void
    {
        $client = $this->client;
        $response = $this->response;

        $response
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $regex1 = '\.manifest';
        $regex2 = 'composer\.lock';

        $returned = [
            0  => [
            'regex' => $regex1,
                'lockFileRegexes' => [
                    0 => $regex2,
                ],
            ],
        ];

        $response
            ->expects($this->atLeastOnce())
            ->method('toArray')
            ->willReturn($returned)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                Request::METHOD_GET,
                '/open/files/supported-formats',
            )
            ->willReturn($response);

        $util = $this->createUtil($client);

        $actual = $util->getSupportedFormats();

        $this->assertSame([$regex1, $regex2], $actual);
    }

    public function testGetSupportedFormatsException(): void
    {
        $this->expectException(BadResponseException::class);

        $client = $this->client;
        $response = $this->response;

        $response
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_BAD_REQUEST)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $util = $this->createUtil($client);

        $util->getSupportedFormats();
    }
}