<?php

namespace ZampTax;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;

class ZampTax extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $context = $installContext->getContext();
        
        // Getting the rule repository
        $ruleRepo = $this->container->get('rule.repository');

        // Defining rule data structure
        $ruleData = [
            'name' => 'Zamp Rule',
            'priority' => 0,
            'conditions' => [
                [
                    'type' => 'cartCartAmount', // Adjusted type if necessary
                    'value' => [
                        'operator' => '>=',
                        'amount' => 0,
                    ],
                ],
            ],
        ];

        // Creating the rule in the repository
        $ruleRepo->create([$ruleData], $context);

        // Fetch the rule ID by searching for the rule by name
        $rules_criteria = new Criteria();
        $rules_criteria->addFilter(new EqualsFilter('name', 'Customers from USA'));
        $ruleId = $ruleRepo->searchIds($rules_criteria, $context)->firstId();

		$taxProId = Uuid::randomHex();

		$lang_criteria = new Criteria();
		$lang_criteria->addFilter(new EqualsFilter('name', 'English (US)'));
		$lang_repo = $this->container->get('language.repository');
		$langId = $lang_repo->searchIds($lang_criteria, $context)->firstId();

        // Getting the tax provider repository
        $taxRepo = $this->container->get('tax_provider.repository');

		$taxProTran = $this->container->get('tax_provider_translation.repository');

        // Defining tax provider data structure
        $taxProviderData = [
            [
                'id' => $taxProId,
                'identifier' => \ZampTax\Checkout\Cart\Tax\ZampTax::class,
                'priority' => 1,
                'active' => false, // Activate this via the `activate` lifecycle method
                'availabilityRuleId' => $ruleId,
            ],
        ];

		$taxProviderTranslationData = [
			[
				'taxProviderId' => $taxProId,
				'languageId' => $langId,
				'name' => 'Zamp Tax'
			]
		];

        // Creating the tax provider in the repository
        $taxRepo->create($taxProviderData, $context);

		$taxProTran->create($taxProviderTranslationData, $context);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
			
			$tax_repo = $this->container->get('tax_provider.repository');

			$context = $uninstallContext->getContext();

			$tax_pro_criteria = new Criteria();

			$tax_pro_criteria->addFilter(new EqualsFilter('identifier', 'ZampTax\Checkout\Cart\Tax\ZampTax'));
		
			$tax_pro_id = $tax_repo->searchIds($tax_pro_criteria, $context)->firstId();

			if($tax_pro_id){

				$tax_repo->update([
					[
						'id' => $tax_pro_id,
						'availabilityRuleId' => null
					]
					], $context);
		
				$tax_repo->delete([
					[
						'id' => $tax_pro_id
					]
				], $context);
			}

			return;
        } else {
			$context = $uninstallContext->getContext();

			$connection = $this->container->get(Connection::class);
	
			$tax_repo = $this->container->get('tax_provider.repository');

			$rule_repo = $this->container->get('rule.repository');

			$rule_crit = new Criteria();

			$rule_crit->addFilter(new EqualsFilter('name', 'Zamp Rule'));

			$rule_id = $rule_repo->searchIds($rule_crit, $context)->firstId();

			$tax_pro_criteria = new Criteria();

			$tax_pro_criteria->addFilter(new EqualsFilter('identifier', 'ZampTax\Checkout\Cart\Tax\ZampTax'));
			
			$tax_pro_id = $tax_repo->searchIds($tax_pro_criteria, $context)->firstId();

			// $zamp_settings_id = $zamp_settings->searchIds(new Criteria(), $uninstallContext)->firstId();
			
			
			if($tax_pro_id){

				$tax_repo->update([
					[
						'id' => $tax_pro_id,
						'availabilityRuleId' => null
					]
					], $context);
		
				$tax_repo->delete([
					[
						'id' => $tax_pro_id
					]
				], $context);
			}

			if($rule_id){
				$rule_repo->delete([
					[
						'id' => $rule_id
					]
				], $context);
			}

			// Drop the 'tax_exempt_code' column from the 'customer_group' table if it exists
			$schemaManager = method_exists($connection, 'createSchemaManager') ? $connection->createSchemaManager() : $connection->getSchemaManager();
			$columns_one = $schemaManager->listTableColumns('customer_group');
			$columns_two = $schemaManager->listTableColumns('product');
		
			if (array_key_exists('tax_exempt_code', $columns_one)) {
				$connection->executeStatement('
					ALTER TABLE `customer_group`
					DROP COLUMN `tax_exempt_code`
				');
			}

			if (array_key_exists('product_tax_code', $columns_two)) {
				$connection->executeStatement('
					ALTER TABLE `product`
					DROP COLUMN `product_tax_code`
				');
			}

			// Disable foreign key checks to avoid errors
			$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0;');

			$tables = [
				'zamp_transactions',
				'zamp_settings',
				'zamp_product_tax_code'
			];

			foreach($tables as $table){
				$connection->executeStatement("DROP TABLE IF EXISTS `$table`");
			}

			// Re-enable foreign key checks after dropping tables
			$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');
		}

		


        // Remove or deactivate the data created by the plugin
    }

    public function activate(ActivateContext $activateContext): void
    {
        // Activate entities, such as a new payment method
        // Or create new entities here, because now your plugin is installed and active for sure
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        // Deactivate entities, such as a new payment method
        // Or remove previously created entities
    }

    public function update(UpdateContext $updateContext): void
    {
        // Update necessary stuff, mostly non-database related
    }

    public function postInstall(InstallContext $installContext): void
    {
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
    }
}
