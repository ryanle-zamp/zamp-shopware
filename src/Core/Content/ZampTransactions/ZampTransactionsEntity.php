<?php

namespace ZampTax\Core\Content\ZampTransactions;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ZampTransactionsEntity extends Entity
{
    use EntityIdTrait;
	/**
	 * @var string
	 */
    protected $orderId;
	/**
	 * @var string
	 */
    protected $firstVersionId;
	/**
	 * @var string
	 */	
	protected $orderNumber;
	/**
	 * @var string
	 */
    protected $currentIdSuffix;
	/**
	 * @var string
	 */
    protected $status;

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getFirstVersionId(): ?string
    {
        return $this->firstVersionId;
    }

    public function setFirstVersionId(?string $firstVersionId): void
    {
        $this->firstVersionId = $firstVersionId;
    }

	public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getCurrentIdSuffix(): ?string
    {
        return $this->currentIdSuffix;
    }

    public function setCurrentIdSuffix(?string $currentIdSuffix): void
    {
        $this->currentIdSuffix = $currentIdSuffix;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

}