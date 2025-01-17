<?php

namespace ZampTax\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1737129409ZampTransactions extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1737129409;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `zamp_transactions` (
    `id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
	`updated_at` DATETIME(3),
    `order_id` VARCHAR(255) DEFAULT NULL,
    `first_version_id` VARCHAR(255) DEFAULT NULL,
	`order_number` VARCHAR(255) DEFAULT NULL,
    `current_id_suffix` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }
}
