<?php

namespace App\HttpClient;

use App\Traits\BadResponseTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * An exception to match status codes to debricked's error descriptions.
 */
class BadResponseException extends HttpException
{
    use BadResponseTrait;

    /**
     * @param int $statusCode
     * @param Throwable|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct(int $statusCode, Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $message = $this->getDescriptionFromStatus($statusCode);
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}