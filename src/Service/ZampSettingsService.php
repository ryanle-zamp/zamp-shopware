<?php

namespace ZampTax\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Service for managing Zamp tax calculation settings
 */
class ZampSettingsService
{
	 /** @var EntityRepository Zamp settings repository */
    private $zampSettingsRepository;

    /**
     * Constructor
     * 
     * @param EntityRepository $zampSettingsRepository Repository for Zamp settings
     */
    public function __construct(EntityRepository $zampSettingsRepository)
    {
        $this->zampSettingsRepository = $zampSettingsRepository;
    }

    /**
     * Creates a new empty Zamp settings entry
     */
    public function createZampSettings(): void
    {
        $context = Context::createDefaultContext();

        $settingsId = Uuid::randomHex();

        $this->zampSettingsRepository->create([
            [
                'id' => $settingsId,
                'apiToken' => '',
                'taxableStates' => '',
                'calculationsEnabled' => 0,
                'transactionsEnabled' => 0
            ]
        ], $context);
    }

    /**
     * Reads Zamp settings
     * 
     * @param Context $context Shopware context
     */
    public function readZampSettings(Context $context): void
    {
        $criteria = new Criteria();

        $zampSettings = $this->zampSettingsRepository->search($criteria, $context)->first();
    }

     /**
     * Updates the API token in Zamp settings
     * 
     * @param Context $context Shopware context
     */
    public function updateZampSettingsToken(Context $context): void
    {
        $criteria = new Criteria();

        $settingsId = $this->zampSettingsRepository->searchIds($criteria)->firstId();

        $this->zampSettingsRepository->update([
            [
                'id' => $settingsId,
                'apiToken' => $context
            ]
        ], $context);        
    }

    /**
     * Updates the taxable states in Zamp settings
     * 
     * @param Context $context Shopware context
     */
    public function updateZampSettingsStates(Context $context): void
    {
        $criteria = new Criteria();

        $settingsId = $this->zampSettingsRepository->searchIds($criteria)->firstId();

        $this->zampSettingsRepository->update([
            [
                'id' => $settingsId,
                'taxableStates' => $context
            ]
        ], $context);        
    }

     /**
     * Updates the transactions enabled flag in Zamp settings
     * 
     * @param Context $context Shopware context
     */
    public function updateZampSettingsCalculations(Context $context): void
    {
        $criteria = new Criteria();

        $settingsId = $this->zampSettingsRepository->searchIds($criteria)->firstId();

        $this->zampSettingsRepository->update([
            [
                'id' => $settingsId,
                'calculationsEnabled' => $context
            ]
        ], $context);        
    }

    /**
     * Updates the retain logs flag in Zamp settings
     * 
     * @param Context $context Shopware context
     */
    public function updateZampSettingsTransactions(Context $context): void
    {
        $criteria = new Criteria();

        $settingsId = $this->zampSettingsRepository->searchIds($criteria)->firstId();

        $this->zampSettingsRepository->update([
            [
                'id' => $settingsId,
                'transactionsEnabled' => $context
            ]
        ], $context);        
    }
    
    
}