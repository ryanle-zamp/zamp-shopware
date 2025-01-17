<?php
namespace ZampTax\Core\Content\CustomerGroup;

use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;

class CustomerGroupExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new StringField('tax_exempt_code', 'taxExemptCode')
        );
    }

    public function getDefinitionClass(): string
    {
        return CustomerGroupDefinition::class;
    }
}