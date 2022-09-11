<?php

namespace App\Model;

/**
 * Stores trigger info from scan.
 */
class Trigger
{
    public const SCAN_IN_PROGRESS = 'Scan is in progress';
    public const SCAN_IS_COMPLETE = 'Scan is complete';
    public const VULNERABILITIES_GREATER_THAN = 'Vulnerabilities greater than';
    public const CVSS_GREATER_THAN = 'CVSS\'s greater than';

    /**
     * @var string
     */
    private string $type;

    /**
     * @var string|int|float|null
     */
    private string|int|float|null $criteria;

    /**
     * @var string|int|float|null
     */
    private string|int|float|null $value;

    /**
     * @param string $type
     * @param int|string|null $criteria
     * @param int|string|null $value
     */
    public function __construct(string $type, string|int|float|null $criteria = null, string|int|float|null $value = null)
    {
        $this->type = $type;
        $this->criteria = $criteria;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string|int|float|null
     */
    public function getCriteria(): string|int|float|null
    {
        return $this->criteria;
    }

    /**
     * @return string|int|float|null
     */
    public function getValue(): string|int|float|null
    {
        return $this->value;
    }
}