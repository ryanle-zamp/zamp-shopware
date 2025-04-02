<?php 

namespace ZampTax\Core\Content\ZampSettings;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

class ZampSettingsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'zamp_settings';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('api_token', 'apiToken')),
            (new StringField('taxable_states', 'taxableStates')),
            (new BoolField('calculations_enabled', 'calculationsEnabled')),
            (new BoolField('transactions_enabled', 'transactionsEnabled'))
        ]);
    }

    public function getEntityClass(): string
    {
        return ZampSettingsEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ZampSettingsCollection::class;
    }
}