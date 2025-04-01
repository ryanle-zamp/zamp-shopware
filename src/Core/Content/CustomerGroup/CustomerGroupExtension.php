<?php
namespace ZampTax\Core\Content\CustomerGroup;

use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;

/**
 * Extends the CustomerGroup entity with tax exemption code field for Zamp tax integration
 */
class CustomerGroupExtension extends EntityExtension
{
    /**
     * Adds custom tax exemption code field to the CustomerGroup entity
     * 
     * @param FieldCollection $collection The field collection to extend
     */
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new StringField('tax_exempt_code', 'taxExemptCode')
        );
    }

    /**
     * Returns the definition class this extension applies to
     * 
     * @return string The fully qualified class name of the extended definition
     */
    public function getDefinitionClass(): string
    {
        return CustomerGroupDefinition::class;
    }
}