<?php

namespace App\Tests\Model;

use App\Model\Trigger;
use PHPUnit\Framework\TestCase;

class TriggerTest extends TestCase
{
    public function testConstants(): void
    {
        $this->assertSame('Scan is in progress', Trigger::SCAN_IN_PROGRESS);
        $this->assertSame('Scan is complete', Trigger::SCAN_IS_COMPLETE);
        $this->assertSame('Vulnerabilities greater than', Trigger::VULNERABILITIES_GREATER_THAN);
        $this->assertSame('CVSS\'s greater than', Trigger::CVSS_GREATER_THAN);
    }

    public function testConstructor(): void
    {
        $type = 'Vulnerabilities greater than';
        $criteria = 6;
        $value = 10;

        $trigger = new Trigger(
            $type,
            $criteria,
            $value,
        );

        $this->assertSame($type, $trigger->getType());
        $this->assertSame($criteria, $trigger->getCriteria());
        $this->assertSame($value, $trigger->getValue());
    }
}