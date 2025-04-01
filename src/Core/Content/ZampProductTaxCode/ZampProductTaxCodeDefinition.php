<?php

namespace ZampTax\Core\Content\ZampProductTaxCode;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

/**
 * Entity definition for product tax codes in Zamp integration
 */
class ZampProductTaxCodeDefinition extends EntityDefinition
{
    /**
     * Entity name in the database
     */
    public const ENTITY_NAME = 'zamp_product_tax_code';

    /**
     * Returns the name of the entity
     * 
     * @return string
     */
    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * Defines the database schema for this entity
     * 
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('product_id', 'productId')),
            (new StringField('product_tax_code', 'productTaxCode'))
        ]);
    }

    /**
     * Returns the fully qualified class name of the entity
     * 
     * @return string
     */
    public function getEntityClass(): string
    {
        return ZampProductTaxCodeEntity::class;
    }

    /**
     * Returns the fully qualified class name of the collection
     * 
     * @return string
     */
    public function getCollectionClass(): string
    {
        return ZampProductTaxCodeCollection::class;
    }
}