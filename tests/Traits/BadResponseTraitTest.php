<?php

namespace App\Tests\Traits;

use App\Traits\BadResponseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Generator;

class BadResponseTraitTest extends TestCase
{
    use BadResponseTrait;

    private function statusCodesProvider(): Generator
    {
        yield [
            Response::HTTP_BAD_REQUEST,
            'Data is missing or given data is invalid.',
        ];

        yield [
            Response::HTTP_UNAUTHORIZED,
            'Missing JWT-token, insufficient privileges or expired JWT-token.',
        ];

        yield [
            Response::HTTP_FORBIDDEN,
            'One or more files/uploads belongs to a different user.',
        ];

        yield [
            Response::HTTP_NOT_FOUND,
            'An upload wasn\'t found with given ID.',
        ];

        yield [
            Response::HTTP_CONFLICT,
            'Unknown API issue.',
        ];
    }

    /**
     * @dataProvider statusCodesProvider
     */
    public function testGetDescriptionFromStatus(int $statusCode, string $expected): void
    {
        $this->assertSame($expected, $this->getDescriptionFromStatus($statusCode));
    }
}