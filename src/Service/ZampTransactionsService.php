<?php

namespace ZampTax\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Service for managing Zamp transaction records
 */
class ZampTransactionsService
{
    /** @var EntityRepository Transactions repository */
    private $zampTransactionsRepository;

    /**
     * Constructor
     * 
     * @param EntityRepository $zampTransactionsRepository Repository for Zamp transactions
     */
    public function __construct(EntityRepository $zampTransactionsRepository)
    {
        $this->zampTransactionsRepository = $zampTransactionsRepository;
    }

    /**
     * Creates a new empty transaction record
     */
    public function createZampTransactions(): void
    {
        $context = Context::createDefaultContext();

        $transactionsId = Uuid::randomHex();

        $this->zampTransactionsRepository->create([
            [
                'id' => $transactionsId,
                'orderId' => '',
                'transId' => '',
                'orderNumber' => '',
                'currentIdSuffix' => '',
                'status' => ''
            ]
        ], $context);
    }

    /**
     * Reads transaction data
     * 
     * @param Context $context Shopware context
     */
    public function readZampTransactions(Context $context): void
    {
        $criteria = new Criteria();

        $zampTransactions = $this->zampTransactionsRepository->search($criteria, $context)->first();
    }

    /**
     * Updates order ID for a transaction
     * 
     * @param Context $context Shopware context
     */
    public function updateZampTransactionsOrderId(Context $context): void
    {
        $criteria = new Criteria();

        $transactionsId = $this->zampTransactionsRepository->searchIds($criteria, $context)->firstId();

        $this->zampTransactionsRepository->update([
            [
                'id' => $transactionsId,
                'orderId' => $context
            ]
        ], $context);        
    }

    /**
     * Updates first version ID for a transaction
     * 
     * @param Context $context Shopware context
     */
    public function updateZampTransactionsFirstVersionId(Context $context): void
    {
        $criteria = new Criteria();

        $transactionsId = $this->zampTransactionsRepository->searchIds($criteria, $context)->firstId();

        $this->zampTransactionsRepository->update([
            [
                'id' => $transactionsId,
                'firstVersionId' => $context
            ]
        ], $context);        
    }

    /**
     * Updates order number for a transaction
     * 
     * @param Context $context Shopware context
     */
    public function updateZampTransactionsOrderNumber(Context $context): void
    {
        $criteria = new Criteria();

        $transactionsId = $this->zampTransactionsRepository->searchIds($criteria, $context)->firstId();

        $this->zampTransactionsRepository->update([
            [
                'id' => $transactionsId,
                'orderNumber' => $context
            ]
        ], $context);        
    }

    /**
     * Updates current ID suffix for a transaction
     * 
     * @param Context $context Shopware context
     */
    public function updateZampTransactionsCurrentIdSuffix(Context $context): void
    {
        $criteria = new Criteria();

        $transactionsId = $this->zampTransactionsRepository->searchIds($criteria, $context)->firstId();

        $this->zampTransactionsRepository->update([
            [
                'id' => $transactionsId,
                'currentIdSuffix' => $context
            ]
        ], $context);        
    }

    /**
     * Updates status for a transaction
     * 
     * @param Context $context Shopware context
     */
    public function updateZampTransactionsStatus(Context $context): void
    {
        $criteria = new Criteria();

        $transactionsId = $this->zampTransactionsRepository->searchIds($criteria)->firstId();

        $this->zampTransactionsRepository->update([
            [
                'id' => $transactionsId,
                'status' => $context
            ]
        ], $context);        
    } 
}