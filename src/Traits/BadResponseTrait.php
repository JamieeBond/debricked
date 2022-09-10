<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\Response;

/**
 * Descriptions to Debricked's bad responses.
 */
trait BadResponseTrait
{
    /**
     * @param int $statusCode
     * @return string
     */
    public function getDescriptionFromStatus(int $statusCode): string
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