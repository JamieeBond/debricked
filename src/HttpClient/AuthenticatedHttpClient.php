<?php

namespace App\HttpClient;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Exception;

/**
 *  Generates a token, to interact with Debricked's API, from login details.
 */
class AuthenticatedHttpClient implements HttpClientInterface
{
    private const BASE_URL = 'https://debricked.com/api';
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
     * @param string $method
     * @param string $path
     * @param array $options
     * @return ResponseInterface
     * @throws TransportExceptionInterface
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

    private function generateToken(): string
    {
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

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new Exception($response->getContent());
        }

        $response = json_decode($response->getContent(), true);

        if (null === $response or !array_key_exists('token', $response)) {
            throw new Exception(sprintf(
                '"%s" returned no token',
                $url
            ));
        }

        return $response['token'];
    }
}