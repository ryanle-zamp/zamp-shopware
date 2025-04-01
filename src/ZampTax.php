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

/**
 * Zamp Tax integration plugin for Shopware 6
 */
class ZampTax extends Plugin
{
    /**
     * Installs the plugin and creates required data
     * 
     * @param InstallContext $installContext Installation context
     */
    public function install(InstallContext $installContext): void
    {
        $context = $installContext->getContext();
        
        $ruleRepo = $this->container->get('rule.repository');

        $ruleData = [
            'name' => 'Zamp Rule',
            'priority' => 0,
            'conditions' => [
                [
                    'type' => 'cartCartAmount',
                    'value' => [
                        'operator' => '>=',
                        'amount' => 0,
                    ],
                ],
            ],
        ];

        $ruleRepo->create([$ruleData], $context);

        $rules_criteria = new Criteria();
        $rules_criteria->addFilter(new EqualsFilter('name', 'Customers from USA'));
        $ruleId = $ruleRepo->searchIds($rules_criteria, $context)->firstId();

        $taxProId = Uuid::randomHex();

        $lang_criteria = new Criteria();
        $lang_criteria->addFilter(new EqualsFilter('name', 'English (US)'));
        $lang_repo = $this->container->get('language.repository');
        $langId = $lang_repo->searchIds($lang_criteria, $context)->firstId();

        $taxRepo = $this->container->get('tax_provider.repository');

        $taxProTran = $this->container->get('tax_provider_translation.repository');

        $taxProviderData = [
            [
                'id' => $taxProId,
                'identifier' => \ZampTax\Checkout\Cart\Tax\ZampTax::class,
                'priority' => 1,
                'active' => false,
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

        $taxRepo->create($taxProviderData, $context);

        $taxProTran->create($taxProviderTranslationData, $context);
    }

    /**
     * Uninstalls the plugin and removes created data if configured
     * 
     * @param UninstallContext $uninstallContext Uninstallation context
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

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

        $connection->executeStatement("DROP TABLE IF EXISTS `zamp_transactions`");
        $connection->executeStatement("DROP TABLE IF EXISTS `zamp_product_tax_code`");
        $connection->executeStatement("DROP TABLE IF EXISTS `zamp_settings`");
    }

    /**
     * Handles plugin activation
     * 
     * @param ActivateContext $activateContext Activation context
     */
    public function activate(ActivateContext $activateContext): void
    {
    }

    /**
     * Handles plugin deactivation
     * 
     * @param DeactivateContext $deactivateContext Deactivation context
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
    }

    /**
     * Handles plugin updates
     * 
     * @param UpdateContext $updateContext Update context
     */
    public function update(UpdateContext $updateContext): void
    {
    }

    /**
     * Executes after plugin installation
     * 
     * @param InstallContext $installContext Installation context
     */
    public function postInstall(InstallContext $installContext): void
    {
    }

    /**
     * Executes after plugin update
     * 
     * @param UpdateContext $updateContext Update context
     */
    public function postUpdate(UpdateContext $updateContext): void
    {
    }
}
