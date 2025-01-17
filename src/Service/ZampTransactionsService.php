<?php

namespace ZampTax\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ZampTransactionsService
{
	/**
	 * @var EntityRepository
	 */
    private $zampTransactionsRepository;

    public function __construct(EntityRepository $zampTransactionsRepository)
    {
        $this->zampTransactionsRepository = $zampTransactionsRepository;
    }

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

    public function readZampTransactions(Context $context): void
    {
        $criteria = new Criteria();

        $zampTransactions = $this->zampTransactionsRepository->search($criteria, $context)->first();
    }

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