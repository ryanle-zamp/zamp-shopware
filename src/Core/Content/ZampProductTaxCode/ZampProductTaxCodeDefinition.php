<?php

namespace ZampTax\Core\Content\ZampProductTaxCode;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

class ZampProductTaxCodeDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'zamp_product_tax_code';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('product_id', 'productId')),
            (new StringField('product_tax_code', 'productTaxCode'))
        ]);
    }

    public function getEntityClass(): string
    {
        return ZampProductTaxCodeEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ZampProductTaxCodeCollection::class;
    }
}