<?php

namespace ZampTax\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ZampSettingsService
{
	/**
	 *
	 * @var EntityRepository
	 */
    private $zampSettingsRepository;

    public function __construct(EntityRepository $zampSettingsRepository)
    {
        $this->zampSettingsRepository = $zampSettingsRepository;
    }

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

    public function readZampSettings(Context $context): void
    {
        $criteria = new Criteria();

        $zampSettings = $this->zampSettingsRepository->search($criteria, $context)->first();
    }

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