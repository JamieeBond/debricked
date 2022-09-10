<?php

namespace App\Util;

use App\HttpClient\AuthenticatedHttpClient;
use App\HttpClient\BadResponseException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Util with common interactions with Debricked's API.
 */
class DebrickedApiUtil
{
    /**
     * Path to upload files.
     */
    private const UPLOAD_FILES_PATH = '/open/uploads/dependencies/files';

    /**
     * Path to conclude files uploads.
     */
    private const CONCLUDE_FILES_UPLOADS_PATH = '/open/finishes/dependencies/files/uploads';

    /**
     * Path to current file status.
     */
    private const CURRENT_FILE_STATUS_PATH = '/open/ci/upload/status';

    /**
     * @var AuthenticatedHttpClient
     */
    private AuthenticatedHttpClient $client;

    /**
     * @param AuthenticatedHttpClient $client
     */
    public function __construct(AuthenticatedHttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $repositoryName
     * @param string $commitName
     * @param string $filePathname
     * @param string|null $ciUploadId
     * @return string
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function uploadDependencyFile(string $repositoryName, string $commitName, string $filePathname, ?string $ciUploadId = null): string
    {
        $data = [
            'repositoryName' => $repositoryName,
            'commitName' => $commitName,
        ];

        if (null !== $ciUploadId) {
            $data['ciUploadId'] = $ciUploadId;
        }

        $data['fileData'] = DataPart::fromPath($filePathname);

        $formData = new FormDataPart($data);

        $options = [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ];

        $response = $this->client->request(
            Request::METHOD_POST,
            self::UPLOAD_FILES_PATH,
            $options
        );

        $statusCode = $response->getStatusCode();

        if (Response::HTTP_OK !== $statusCode) {
            throw new BadResponseException($statusCode);
        }

        return (string) $response->toArray()['ciUploadId'];
    }

    /**
     * @param string $ciUploadId
     * @return string
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function concludeFileUpload(string $ciUploadId): string
    {
        $body = [
            'ciUploadId' => $ciUploadId,
        ];

        $response = $this->client->request(
            Request::METHOD_POST,
            self::CONCLUDE_FILES_UPLOADS_PATH,
            ['json' => $body]
        );

        $statusCode = $response->getStatusCode();

        if (Response::HTTP_NO_CONTENT !== $statusCode) {
            throw new BadResponseException($statusCode);
        }

        return $ciUploadId;
    }

    /**
     * @param string $ciUploadId
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function currentFileStatus(string $ciUploadId): ResponseInterface
    {
        $query = [
            'ciUploadId' => $ciUploadId,
        ];

        return $this->client->request(
            Request::METHOD_GET,
            self::CURRENT_FILE_STATUS_PATH,
            ['query' => $query]
        );
    }
}