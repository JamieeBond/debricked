<?php

namespace App\HttpClient;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 *  Authenticated HTTP client, to interact with Debricked's API, from login credentials.
 */
class AuthenticatedHttpClient implements HttpClientInterface
{
    /**
     * Debricked's base url.
     */
    private const BASE_URL = 'https://debricked.com/api';

    /**
     * Debricked's current API version.
     */
    private const API_VERSION = '1.0';

    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $client;

    /**
     * @var string
     */
    private string $username;

    /**
     * @var string
     */
    private string $password;

    /**
     * Cached token.
     *
     * @var string|null
     */
    private ?string $token = null;

    /**
     * @param HttpClientInterface $client
     * @param string $username
     * @param string $password
     */
    public function __construct(HttpClientInterface $client, string $username, string $password)
    {
        $this->client = $client;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Make authenticated debirkced requests.
     *
     * @param string $method
     * @param string $path
     * @param array $options
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function request(string $method, string $path, array $options = []): ResponseInterface
    {
        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }

        $token = $this->generateToken();
        $options['headers']['Authorization'] = "Bearer {$token}";

        $url = self::BASE_URL.  '/' . self::API_VERSION . $path;

        return $this->client->request(
            $method,
            $url,
            $options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function stream(ResponseInterface|iterable $responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function withOptions(array $options): static
    {
        $this->client->withOptions($options);
    }

    /**
     * Generate token needed for debricked's API authentication.
     *
     * @return string
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function generateToken(): string
    {
        // Use cached token, if multiple calls are made.
        if (null !== $this->token) {
            return $this->token;
        }

        $url = self::BASE_URL.'/login_check';
        $body = [
            '_username' => $this->username,
            '_password' => $this->password,
        ];

        $response = $this->client->request(
            Request::METHOD_POST,
            $url,
            ['json' => $body]
        );

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw new HttpException($statusCode, 'Issue obtaining token, check credentials.');
        }

        $response = json_decode($response->getContent(), true);

        return $this->token = $response['token'];
    }
}