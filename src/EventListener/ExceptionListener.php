<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * An event listener to return exceptions as a json response, rather than rendering them as HTML.
 */
class ExceptionListener
{
    /**
     * @param ExceptionEvent $event
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = new JsonResponse();

        $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

        $message = [
            'type' => 'Error',
            'message' => $exception->getMessage(),
        ];

        $response->setContent(json_encode($message));

        $event->setResponse($response);
    }
}