<?php

namespace App\Controller;

use App\CQRS\Command\UploadCommand;
use App\CQRS\CommandBus;
use App\Util\ValidationUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final class UploadController extends AbstractController
{
    /**
     * @var CommandBus
     */
    private CommandBus $commandBus;

    /**
     * @var ValidationUtil
     */
    private ValidationUtil $validationUtil;

    /**
     * @param CommandBus $commandBus
     * @param ValidationUtil $validationUtil
     */
    public function __construct(CommandBus $commandBus, ValidationUtil $validationUtil)
    {
        $this->commandBus = $commandBus;
        $this->validationUtil = $validationUtil;
    }

    /**
     * @return JsonResponse
     */
    #[Route('/', name: 'app_default')]
    public function index(): JsonResponse
    {
        $message = [
            'title' => 'Dependency Files Rule Engine',
            'info' => 'Visit /upload to upload files',
        ];

        return $this->json(
            $message,
            Response::HTTP_CREATED
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    #[Route('/upload', name: 'app_upload', methods:[Request::METHOD_POST])]
    public function upload(Request $request): JsonResponse
    {
        $command = new UploadCommand(
            $request->get('commitName'),
            $request->get('repositoryName'),
            $request->files->get('files'),
        );

        $this->validationUtil->validateConstraints($command);

        $this->commandBus->dispatch($command);

        $message = [
            'message' => 'Upload Successful',
        ];

        return $this->json(
            $message,
            Response::HTTP_CREATED
        );
    }
}