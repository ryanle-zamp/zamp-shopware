<?php

namespace ZampTax\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Service for managing Zamp product tax codes
 */
class ZampProductTaxCodeService
{
    /** @var EntityRepository Product tax code repository */
    private $zampProductTaxCodeRepository;

    /**
     * Constructor
     * 
     * @param EntityRepository $zampProductTaxCodeRepository Repository for product tax codes
     */
    public function __construct(EntityRepository $zampProductTaxCodeRepository)
    {
        $this->zampProductTaxCodeRepository = $zampProductTaxCodeRepository;
    }

    /**
     * Creates a new empty product tax code entry
     */
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

    /**
     * Reads product tax code data
     * 
     * @param Context $context Shopware context
     */
    public function readProductTaxCode(Context $context): void
    {
        $criteria = new Criteria();

        $zampSettings = $this->zampProductTaxCodeRepository->search($criteria, $context)->first();
    }

    /**
     * Updates product ID for a tax code entry
     * 
     * @param Context $context Shopware context
     */
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

    /**
     * Updates tax code for a product
     * 
     * @param Context $context Shopware context
     */
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