<?php 

namespace ZampTax\Core\Content\ZampTransactions;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(ZampTransactionsEntity $entity)
 * @method void               set(string $key, ZampTransactionsEntity $entity)
 * @method ZampTransactionsEntity[]    getIterator()
 * @method ZampTransactionsEntity[]    getElements()
 * @method ZampTransactionsEntity|null get(string $key)
 * @method ZampTransactionsEntity|null first()
 * @method ZampTransactionsEntity|null last()
 */

class ZampTransactionsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ZampTransactionsEntity::class;
    }
}