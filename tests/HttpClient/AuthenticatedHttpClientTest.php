<?php

namespace App\Tests\HttpClient;

use App\HttpClient\AuthenticatedHttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AuthenticatedHttpClientTest extends TestCase
{
    /**
     * @var MockObject|HttpClientInterface|null
     */
    private MockObject|HttpClientInterface|null $client = null;

    protected function setUp(): void
    {
        $this->client = $this
            ->getMockBuilder(HttpClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    private function createAuthClient(HttpClientInterface $client, string $username, string $password): AuthenticatedHttpClient
    {
        return new AuthenticatedHttpClient(
            $client,
            $username,
            $password
        );
    }

    private function createResponse(): MockObject|ResponseInterface
    {
        return $this
            ->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testRequestSuccessful(): void
    {
        $client = $this->client;

        $username = 'username';
        $password = 'password';
        $baseUrl = 'https://debricked.com/api';
        $token = 'token';
        $requestPath = '/api/test';

        $credentials = [
            '_username' => $username,
            '_password' => $password,
        ];

        $responseOne = $this->createResponse();
        $responseOne
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;
        $responseOne
            ->expects($this->atLeastOnce())
            ->method('getContent')
            ->willReturn('{"token" : "' . $token . '"}')
        ;

        $responseTwo = $this->createResponse();
        $responseTwo
            ->expects($this->never())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $options = [
            'headers' => [
                'Content' => 'keep-alive',
                'Authorization' => "Bearer {$token}",
            ],
        ];

        $client
            ->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                [Request::METHOD_POST, $baseUrl . '/login_check', ['json' => $credentials]],
                [Request::METHOD_GET, $baseUrl . '/1.0' . $requestPath, $options],
            )
            ->willReturnOnConsecutiveCalls($responseOne, $responseTwo)
        ;

        $authClient = $this->createAuthClient(
            $client,
            $username,
            $password
        );

        $response = $authClient->request(
            Request::METHOD_GET,
            $requestPath,
            ['headers' => ['Content' => 'keep-alive'],]
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testRequestException(): void
    {
        $this->expectException(HttpException::class);

        $client = $this->client;

        $response = $this->createResponse();

        $response
            ->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_CONFLICT)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->willReturn($response)
        ;

        $authClient = $this->createAuthClient(
            $client,
            'username',
            'password'
        );

        $authClient->request(
            Request::METHOD_GET,
            '/path'
        );
    }
}