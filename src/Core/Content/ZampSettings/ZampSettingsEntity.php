<?php

namespace ZampTax\Core\Content\ZampSettings;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Entity for storing Zamp tax integration configuration settings
 */
class ZampSettingsEntity extends Entity
{
    use EntityIdTrait;

    /**
     * API token for connecting to Zamp tax services
     * 
     * @var string|null
     */
    protected $apiToken;
    
    /**
     * Comma-separated list of states where tax calculation is enabled
     * 
     * @var string|null
     */
    protected $taxableStates;
    
    /**
     * Whether tax calculations are enabled
     * 
     * @var bool
     */
    protected $calculationsEnabled;
    
    /**
     * Whether tax transactions reporting is enabled
     * 
     * @var bool
     */
    protected $transactionsEnabled;
    
    /**
     * Whether debug logs should be retained
     * 
     * @var bool
     */
    protected $retainLogs;

    /**
     * Gets the API token
     * 
     * @return string|null The API token
     */
    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    /**
     * Sets the API token
     * 
     * @param string|null $apiToken The API token to set
     */
    public function setApiToken(?string $apiToken): void
    {
        $this->apiToken = $apiToken;
    }

    /**
     * Gets the taxable states
     * 
     * @return string|null Comma-separated list of state codes
     */
    public function getTaxableStates(): ?string
    {
        return $this->taxableStates;
    }

    /**
     * Sets the taxable states
     * 
     * @param string|null $taxableStates Comma-separated list of state codes
     */
    public function setTaxableStates(?string $taxableStates): void
    {
        $this->taxableStates = $taxableStates;
    }

    /**
     * Checks if tax calculations are enabled
     * 
     * @return bool True if enabled
     */
    public function getCalculationsEnabled(): bool
    {
        return $this->calculationsEnabled;
    }

    /**
     * Sets whether tax calculations are enabled
     * 
     * @param bool $calculationsEnabled Enable/disable tax calculations
     */
    public function setCalculationsEnabled(bool $calculationsEnabled): void
    {
        $this->calculationsEnabled = $calculationsEnabled;
    }

    /**
     * Checks if tax transactions are enabled
     * 
     * @return bool True if enabled
     */
    public function getTransactionsEnabled(): bool
    {
        return $this->transactionsEnabled;
    }

    /**
     * Sets whether tax transactions are enabled
     * 
     * @param bool $transactionsEnabled Enable/disable tax transactions
     */
    public function setTransactionsEnabled(bool $transactionsEnabled): void
    {
        $this->transactionsEnabled = $transactionsEnabled;
    }

    /**
     * Checks if debug logs should be retained
     * 
     * @return bool True if logs should be retained
     */
    public function getRetainLogs(): bool
    {
        return $this->retainLogs;
    }

    /**
     * Sets whether debug logs should be retained
     * 
     * @param bool $retainLogs Enable/disable log retention
     */
    public function setRetainLogs(bool $retainLogs): void
    {
        $this->retainLogs = $retainLogs;
    }
}