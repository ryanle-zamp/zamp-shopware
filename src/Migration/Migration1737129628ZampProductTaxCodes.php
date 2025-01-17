<?php

namespace ZampTax\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1737129628ZampProductTaxCodes extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1737129628;
    }

    public function update(Connection $connection): void
    {
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `zamp_product_tax_code` (
    `id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
	`updated_at` DATETIME(3),
    `product_id` VARCHAR(255) DEFAULT NULL,
    `product_tax_code` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Destructive changes go here
    }
}
