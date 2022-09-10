<?php

namespace App\HttpClient;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * An exception to match status codes to debricked's error descriptions.
 */
class ResponseException extends HttpException
{
    /**
     * @param int $statusCode
     * @param Throwable|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct(int $statusCode, Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $this->getDescription($statusCode);
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    /**
     * @param int $statusCode
     * @return string
     */
    private function getDescription(int $statusCode)
    {
        return match ($statusCode) {
            Response::HTTP_BAD_REQUEST => 'Data is missing or given data is invalid.',
            Response::HTTP_UNAUTHORIZED => 'Missing JWT-token, insufficient privileges or expired JWT-token.',
            Response::HTTP_FORBIDDEN => 'One or more files/uploads belongs to a different user.',
            Response::HTTP_NOT_FOUND => 'An upload wasn\'t found with given ID.',
            default => 'Unknown API issue.',
        };
    }
}