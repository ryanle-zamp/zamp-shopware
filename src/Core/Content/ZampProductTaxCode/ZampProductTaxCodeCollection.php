<?php 

namespace ZampTax\Core\Content\ZampProductTaxCode;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(ZampProductTaxCodeEntity $entity)
 * @method void               set(string $key, ZampProductTaxCodeEntity $entity)
 * @method ZampProductTaxCodeEntity[]    getIterator()
 * @method ZampProductTaxCodeEntity[]    getElements()
 * @method ZampProductTaxCodeEntity|null get(string $key)
 * @method ZampProductTaxCodeEntity|null first()
 * @method ZampProductTaxCodeEntity|null last()
 */

class ZampProductTaxCodeCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ZampProductTaxCodeEntity::class;
    }
}