<?php

namespace App\Util;

use App\HttpClient\AuthenticatedHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Exception;

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
    public function uploadDependencyFile(string $repositoryName, string $commitName, string $filePathname, ?string $ciUploadId = null)
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

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new Exception($response->getContent());
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

        if (Response::HTTP_NO_CONTENT !== $response->getStatusCode()) {
            throw new Exception($response->getContent());
        }

        return $ciUploadId;
    }
}