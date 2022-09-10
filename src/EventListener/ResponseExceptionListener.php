<?php

namespace App\EventListener;

use App\HttpClient\ResponseException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * An event listener to return debricked's API bad responses as json error messages.
 */
class ResponseExceptionListener
{
    /**
     * @param ExceptionEvent $event
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $exception = $exception->getPrevious();

        // Only care about the original exception
        if (!$exception instanceof ResponseException) {
            return;
        }

        $message = [
            'info' => 'Debricked API bad response.',
            'error' => $exception->getMessage(),
        ];

        $response = new JsonResponse(
            $message,
            $exception->getStatusCode()
        );

        $event->setResponse($response);
    }
}