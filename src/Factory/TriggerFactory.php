<?php

namespace App\Factory;

use App\Model\Trigger;

/**
 * A simple factory for creating a Trigger, allows for testing of what triggers are being created.
 */
class TriggerFactory
{
    /**
     * @param string $type
     * @param string|int|float|null $criteria
     * @param string|int|float|null $value
     * @return Trigger
     */
    public function create(string $type, string|int|float|null $criteria = null, string|int|float|null $value = null): Trigger
    {
        return new Trigger(
            $type,
            $criteria,
            $value
        );
    }
}