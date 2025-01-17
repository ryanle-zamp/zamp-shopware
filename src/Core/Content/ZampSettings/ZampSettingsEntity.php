<?php

namespace ZampTax\Core\Content\ZampSettings;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ZampSettingsEntity extends Entity
{
    use EntityIdTrait;

	/**
	 * @var string
	 */
    protected $apiToken;
	/**
	 * @var string
	 */
    protected $taxableStates;
	/**
	 * @var bool
	 */
    protected $calculationsEnabled;
	/**
	 * @var bool
	 */
    protected $transactionsEnabled;
	/**
	 * @var bool
	 */
    protected $retainLogs;

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): void
    {
        $this->apiToken = $apiToken;
    }

    public function getTaxableStates(): ?string
    {
        return $this->taxableStates;
    }

    public function setTaxableStates(?string $taxableStates): void
    {
        $this->taxableStates = $taxableStates;
    }

    public function getCalculationsEnabled(): bool
    {
        return $this->calculationsEnabled;
    }

    public function setCalculationsEnabled(bool $calculationsEnabled): void
    {
        $this->calculationsEnabled = $calculationsEnabled;
    }

    public function getTransactionsEnabled(): bool
    {
        return $this->transactionsEnabled;
    }

    public function setTransactionsEnabled(bool $transactionsEnabled): void
    {
        $this->transactionsEnabled = $transactionsEnabled;
    }

    public function getRetainLogs(): bool
    {
        return $this->retainLogs;
    }

    public function setRetainLogs(bool $retainLogs): void
    {
        $this->retainLogs = $retainLogs;
    }

}