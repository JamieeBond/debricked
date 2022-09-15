<?php

namespace App\Tests\Util;

use App\CQRS\Command\UploadCommand;
use App\Util\ValidationUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationUtilTest extends TestCase
{
    /**
     * @var MockObject|ValidatorInterface|null
     */
    private MockObject|ValidatorInterface|null $validator = null;

    /**
     * @var MockObject|UploadCommand|null
     */
    private MockObject|UploadCommand|null $command = null;

    /**
     * @var MockObject|ConstraintViolation|null
     */
    private MockObject|ConstraintViolation|null $violation = null;

    protected function setUp(): void
    {
        $this->validator = $this
            ->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->command = $this
            ->getMockBuilder(UploadCommand::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->violation = $this
            ->getMockBuilder(ConstraintViolation::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    private function createUtil(ValidatorInterface $validator): ValidationUtil
    {
        return new ValidationUtil($validator);
    }

    private function createViolationList(iterable $violations = []): ConstraintViolationList
    {
        return new ConstraintViolationList($violations);
    }

    public function testValidateConstraintsResponseHasViolations(): void
    {
        $command = $this->command;
        $validator = $this->validator;
        $violation = $this->violation;

        $violationList = $this->createViolationList([$violation]);

        $validator
            ->expects($this->once())
            ->method('validate')
            ->with($command)
            ->willReturn($violationList)
        ;

        $util = $this->createUtil($validator);

        $this->assertInstanceOf(
            JsonResponse::class,
            $util->validateConstraintsResponse($command)
        );
    }

    public function testValidateConstraintsResponseNoViolations(): void
    {
        $command = $this->command;
        $validator = $this->validator;

        $violationList = $this->createViolationList([]);

        $validator
            ->expects($this->once())
            ->method('validate')
            ->with($command)
            ->willReturn($violationList)
        ;

        $util = $this->createUtil($validator);

        $this->assertNull($util->validateConstraintsResponse($command));
    }
}