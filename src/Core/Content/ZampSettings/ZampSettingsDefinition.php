<?php 

namespace ZampTax\Core\Content\ZampSettings;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

/**
 * Entity definition for configuration settings in Zamp tax integration
 */
class ZampSettingsDefinition extends EntityDefinition
{
    /**
     * Entity name in the database
     */
    public const ENTITY_NAME = 'zamp_settings';

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
            (new StringField('api_token', 'apiToken')),
            (new StringField('taxable_states', 'taxableStates')),
            (new BoolField('calculations_enabled', 'calculationsEnabled')),
            (new BoolField('transactions_enabled', 'transactionsEnabled'))
        ]);
    }

     /**
     * Returns the fully qualified class name of the entity
     * 
     * @return string
     */
    public function getEntityClass(): string
    {
        return ZampSettingsEntity::class;
    }

    /**
     * Returns the fully qualified class name of the collection
     * 
     * @return string
     */
    public function getCollectionClass(): string
    {
        return ZampSettingsCollection::class;
    }
}