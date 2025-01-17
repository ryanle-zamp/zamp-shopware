<?php

namespace ZampTax\Core\Content\ZampTransactions;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

class ZampTransactionsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'zamp_transactions';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

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

    public function getEntityClass(): string
    {
        return ZampTransactionsEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ZampTransactionsCollection::class;
    }
}