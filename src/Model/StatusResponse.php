<?php

namespace App\Model;

/**
 * Model for json response from Debricked's status.
 */
class StatusResponse
{
    /**
     * @var int
     */
    private int $progress;

    /**
     * @var int
     */
    private int $vulnerabilitiesFound;

    /**
     * @var int
     */
    private int $unaffectedVulnerabilitiesFound;

    /**
     * @var array
     */
    private array $automationRules;

    /**
     * @return int
     */
    public function getProgress(): int
    {
        return $this->progress;
    }

    /**
     * @param int $progress
     * @return StatusResponse
     */
    public function setProgress(int $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    /**
     * @return int
     */
    public function getVulnerabilitiesFound(): int
    {
        return $this->vulnerabilitiesFound;
    }

    /**
     * @param int $vulnerabilitiesFound
     * @return StatusResponse
     */
    public function setVulnerabilitiesFound(int $vulnerabilitiesFound): self
    {
        $this->vulnerabilitiesFound = $vulnerabilitiesFound;
        return $this;
    }

    /**
     * @return int
     */
    public function getUnaffectedVulnerabilitiesFound(): int
    {
        return $this->unaffectedVulnerabilitiesFound;
    }

    /**
     * @param int $unaffectedVulnerabilitiesFound
     * @return StatusResponse
     */
    public function setUnaffectedVulnerabilitiesFound(int $unaffectedVulnerabilitiesFound): self
    {
        $this->unaffectedVulnerabilitiesFound = $unaffectedVulnerabilitiesFound;
        return $this;
    }

    /**
     * @return array
     */
    public function getAutomationRules(): array
    {
        return $this->automationRules;
    }

    /**
     * @param array $automationRules
     * @return StatusResponse
     */
    public function setAutomationRules(array $automationRules): self
    {
        $this->automationRules = $automationRules;
        return $this;
    }

    /**
     * Get the highest vulnerability CVSS rating.
     *
     * @return float
     */
    public function getMaxCvss(): float
    {
        $cvss = [];

        foreach ($this->automationRules as $rule) {
            foreach ($rule['triggerEvents'] as $event) {
                $cvss3 = $event['cvss3'];
                /**
                 * CVSS3 is recommended but is not always available.
                 * Revert to CVSS2 if not available.
                 */
                $cvss[] = $cvss3 !== null ? $cvss3 : $event['cvss2'];
            }
        }

        return 0 === count($cvss) ? 0 : max($cvss);
    }
}