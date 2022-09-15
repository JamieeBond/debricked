<?php

namespace App\Tests\Factory;

use App\Factory\TriggerFactory;
use App\Model\Trigger;
use PHPUnit\Framework\TestCase;

class TriggerFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $type = 'type1';
        $criteria = 9;
        $value = 10;

        $factory = new TriggerFactory();

        $trigger = $factory->create(
            $type,
            $criteria,
            $value
        );

        $this->assertInstanceOf(Trigger::class, $trigger);
        $this->assertSame($type, $trigger->getType());
        $this->assertSame($criteria, $trigger->getCriteria());
        $this->assertSame($value, $trigger->getValue());
    }
}