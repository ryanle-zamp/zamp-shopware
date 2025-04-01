<?php

namespace ZampTax\Core\Content\ZampTransactions;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Entity representing a Zamp tax transaction record
 */
class ZampTransactionsEntity extends Entity
{
    use EntityIdTrait;

    /**
     * The order ID associated with this transaction
     * 
     * @var string|null
     */
    protected $orderId;

    /**
     * The first version ID of the associated order
     * 
     * @var string|null
     */
    protected $firstVersionId;

    /**
     * The order number associated with this transaction
     * 
     * @var string|null
     */
    protected $orderNumber;

    /**
     * The current ID suffix for incremental transaction tracking
     * 
     * @var string|null
     */
    protected $currentIdSuffix;

    /**
     * The status of the transaction
     * 
     * @var string|null
     */
    protected $status;

    /**
     * Gets the order ID
     * 
     * @return string|null The order ID
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    /**
     * Sets the order ID
     * 
     * @param string|null $orderId The order ID to set
     */
    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * Gets the first version ID
     * 
     * @return string|null The first version ID
     */
    public function getFirstVersionId(): ?string
    {
        return $this->firstVersionId;
    }

    /**
     * Sets the first version ID
     * 
     * @param string|null $firstVersionId The first version ID to set
     */
    public function setFirstVersionId(?string $firstVersionId): void
    {
        $this->firstVersionId = $firstVersionId;
    }

    /**
     * Gets the order number
     * 
     * @return string|null The order number
     */
    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    /**
     * Sets the order number
     * 
     * @param string|null $orderNumber The order number to set
     */
    public function setOrderNumber(?string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * Gets the current ID suffix
     * 
     * @return string|null The current ID suffix
     */
    public function getCurrentIdSuffix(): ?string
    {
        return $this->currentIdSuffix;
    }

    /**
     * Sets the current ID suffix
     * 
     * @param string|null $currentIdSuffix The current ID suffix to set
     */
    public function setCurrentIdSuffix(?string $currentIdSuffix): void
    {
        $this->currentIdSuffix = $currentIdSuffix;
    }

    /**
     * Gets the status
     * 
     * @return string|null The status
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Sets the status
     * 
     * @param string|null $status The status to set
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }
}