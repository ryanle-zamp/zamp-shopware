<?php

namespace ZampTax\Core\Content\ZampSettings;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(ZampSettingsEntity $entity)
 * @method void               set(string $key, ZampSettingsEntity $entity)
 * @method ZampSettingsEntity[]    getIterator()
 * @method ZampSettingsEntity[]    getElements()
 * @method ZampSettingsEntity|null get(string $key)
 * @method ZampSettingsEntity|null first()
 * @method ZampSettingsEntity|null last()
 */

class ZampSettingsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ZampSettingsEntity::class;
    }
}