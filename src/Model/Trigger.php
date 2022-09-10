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
     * @var string|int|null
     */
    private null|string|int $criteria;

    /**
     * @var string|int|null
     */
    private null|string|int $value;

    /**
     * @param string $type
     * @param int|string|null $criteria
     * @param int|string|null $value
     */
    public function __construct(string $type, int|string|null $criteria = null, int|string|null $value = null)
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
     * @return int|string|null
     */
    public function getCriteria(): int|string|null
    {
        return $this->criteria;
    }

    /**
     * @return int|string|null
     */
    public function getValue(): int|string|null
    {
        return $this->value;
    }
}