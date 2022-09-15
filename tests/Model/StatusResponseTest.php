<?php

namespace App\Tests\Model;

use App\Model\StatusResponse;
use PHPUnit\Framework\TestCase;

class StatusResponseTest extends TestCase
{
    private function createStatusResponse(): StatusResponse
    {
        return new StatusResponse();
    }

    public function testSetGetProgress(): void
    {
        $statusResponse = $this->createStatusResponse();
        $value = 89;
        $statusResponse->setProgress($value);
        $this->assertSame($value, $statusResponse->getProgress());
    }

    public function testSetGetVulnerabilitiesFound(): void
    {
        $statusResponse = $this->createStatusResponse();
        $value = 5;
        $statusResponse->setVulnerabilitiesFound($value);
        $this->assertSame($value, $statusResponse->getVulnerabilitiesFound());
    }

    public function testSetGetUnaffectedVulnerabilitiesFound(): void
    {
        $statusResponse = $this->createStatusResponse();
        $value = 1;
        $statusResponse->setUnaffectedVulnerabilitiesFound($value);
        $this->assertSame($value, $statusResponse->getUnaffectedVulnerabilitiesFound());
    }

    public function testSetGetAutomationRules(): void
    {
        $statusResponse = $this->createStatusResponse();
        $value = ['rules'];
        $statusResponse->setAutomationRules($value);
        $this->assertSame($value, $statusResponse->getAutomationRules());
    }

    public function testSetGetDetailsUrl(): void
    {
        $statusResponse = $this->createStatusResponse();
        $value = 'www.debricked.com';
        $statusResponse->setDetailsUrl($value);
        $this->assertSame($value, $statusResponse->getDetailsUrl());
    }

    public function testGetMaxCvss(): void
    {
        $statusResponse = $this->createStatusResponse();
        $maxValue = 5.6;

        $values = [
            0 => [
                'triggerEvents' => [
                    0 => [
                        'cvss2' => 3.5,
                        'cvss3' => 4.5,
                    ],
                ],
            ],
            1 => [
                'triggerEvents' => [
                    0 => [
                        'cvss2' => 3.5,
                        'cvss3' => $maxValue,
                    ],
                ],
            ],
            2 => [
                'triggerEvents' => [
                    0 => [
                        'cvss2' => 3.5,
                        'cvss3' => null,
                    ],
                ],
            ],
        ];

        $statusResponse->setAutomationRules($values);
        $this->assertSame($maxValue, $statusResponse->getMaxCvss());
    }
}