<?php

namespace App\Tests\Controller;

use App\Controller\UploadController;
use App\CQRS\Command\UploadCommand;
use App\CQRS\CommandBus;
use App\Util\ValidationUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadControllerTest extends TestCase
{
    /**
     * @var MockObject|CommandBus|null
     */
    private MockObject|CommandBus|null $commandBus = null;

    /**
     * @var MockObject|ValidationUtil|null
     */
    private MockObject|ValidationUtil|null $validationUtil = null;

    /**
     * @var MockObject|ContainerInterface|null
     */
    private MockObject|ContainerInterface|null $container = null;

    protected function setUp(): void
    {
        $this->commandBus = $this
            ->getMockBuilder(CommandBus::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->validationUtil = $this
            ->getMockBuilder(ValidationUtil::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->container = $this
            ->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    private function createController(CommandBus $commandBus, ValidationUtil $validationUtil): UploadController
    {
        $controller = new UploadController(
            $commandBus,
            $validationUtil
        );

        $controller->setContainer($this->container);

        return $controller;
    }

    public function testIndex(): void
    {
        $controller = $this->createController(
            $this->commandBus,
            $this->validationUtil
        );

        $response = $controller->index();

        $content = '{"title":"Dependency Files Rule Engine","info":"Visit \/upload to upload files"}';
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($content, $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testUpload(): void
    {
        $commandBus = $this->commandBus;

        $commandBus
            ->method('dispatch')
            ->with($this->isInstanceOf(UploadCommand::class))
        ;

        $validationUtil = $this->validationUtil;

        $validationUtil
            ->method('validateConstraintsResponse')
            ->with($this->isInstanceOf(UploadCommand::class))
            ->willReturn(null)
        ;

        $controller = $this->createController(
            $commandBus,
            $validationUtil
        );

        $response = $controller->upload(new Request());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('{"message":"Upload Successful"}', $response->getContent());
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }
}