<?php

namespace App\Tests\Validator;

use App\Util\DebrickedApiUtil;
use App\Validator\SupportedFile;
use App\Validator\SupportedFileValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SupportedFileValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): SupportedFileValidator
    {
        $apiUtil = $this
            ->getMockBuilder(DebrickedApiUtil::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $apiUtil
            ->expects($this->atLeastOnce())
            ->method('getSupportedFormats')
            ->willReturn(['composer\.lock'])
        ;

        return new SupportedFileValidator($apiUtil);
    }

    private function createFile(string $name): UploadedFile
    {
        $file = $this
            ->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $file
            ->expects($this->atLeastOnce())
            ->method('getClientOriginalName')
            ->willReturn($name)
        ;

        return $file;
    }

    public function testSupportedFile(): void
    {
        $file = $this->createFile('composer.lock');

        $this->validator->validate([$file], new SupportedFile());

        $this->assertNoViolation();
    }

    public function testNotInstanceOfUploadedFile(): void
    {
        $file = ['test'];

        $this->validator->validate([$file], new SupportedFile());

        $this->assertNoViolation();
    }

    public function testNotSupportedFile(): void
    {
        $name = 'composer.pdf';
        $file = $this->createFile($name);

        $this->validator->validate([$file], new SupportedFile());

        $this
            ->buildViolation('"{{ filename }}" is not supported.')
            ->setParameter('{{ filename }}', $name)
            ->assertRaised()
        ;
    }
}