<?php

namespace ZampTax\Core\Content\ZampTransactions;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

/**
 * Entity definition for Zamp transaction records
 */
class ZampTransactionsDefinition extends EntityDefinition
{
    /**
     * Entity name in the database
     */
    public const ENTITY_NAME = 'zamp_transactions';

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
            (new StringField('order_id', 'orderId')),
            (new StringField('order_number', 'orderNumber')),
            (new StringField('current_id_suffix', 'currentIdSuffix')),
            (new StringField('status', 'status')),
            (new StringField('first_version_id', 'firstVersionId'))
        ]);
    }

    /**
     * Returns the class name of the entity
     * 
     * @return string
     */
    public function getEntityClass(): string
    {
        return ZampTransactionsEntity::class;
    }

    /**
     * Returns the class name of the collection
     * 
     * @return string
     */
    public function getCollectionClass(): string
    {
        return ZampTransactionsCollection::class;
    }
}