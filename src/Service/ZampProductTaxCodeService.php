<?php

namespace ZampTax\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ZampProductTaxCodeService
{
	/**
	 * @var EntityRepository
	 */
    private $zampProductTaxCodeRepository;

    public function __construct(EntityRepository $zampProductTaxCodeRepository)
    {
        $this->zampProductTaxCodeRepository = $zampProductTaxCodeRepository;
    }

    public function createZampProductTaxCode(): void
    {
        $context = Context::createDefaultContext();

        $settingsId = Uuid::randomHex();

        $this->zampProductTaxCodeRepository->create([
            [
                'id' => $settingsId,
                'productId' => '',
                'productTaxCode' => '',
            ]
        ], $context);
    }

    public function readProductTaxCode(Context $context): void
    {
        $criteria = new Criteria();

        $zampSettings = $this->zampProductTaxCodeRepository->search($criteria, $context)->first();
    }

    public function updateZampProductId(Context $context): void
    {
        $criteria = new Criteria();

        $settingsId = $this->zampProductTaxCodeRepository->searchIds($criteria)->firstId();

        $this->zampProductTaxCodeRepository->update([
            [
                'id' => $settingsId,
                'productId' => $context
            ]
        ], $context);        
    }

    public function updateZampProductTaxCode(Context $context): void
    {
        $criteria = new Criteria();

        $settingsId = $this->zampProductTaxCodeRepository->searchIds($criteria)->firstId();

        $this->zampProductTaxCodeRepository->update([
            [
                'id' => $settingsId,
                'productTaxCode' => $context
            ]
        ], $context);        
    }    
    
}