<?php

namespace App\Tests\HttpClient;

use App\HttpClient\BadResponseException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BadResponseExceptionTest extends TestCase
{
    public function testExtendsHttpException(): void
    {
        $exception = new BadResponseException(Response::HTTP_UNAUTHORIZED);
        $this->assertInstanceOf(HttpException::class, $exception);
    }
}