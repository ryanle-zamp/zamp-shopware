<?php

namespace ZampTax\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1737128900ZampSettings extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1737128900;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `zamp_settings` (
    `id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
	`updated_at` DATETIME(3),
    `api_token` VARCHAR(255) DEFAULT NULL,    
    `taxable_states` VARCHAR(255) DEFAULT Null,
    `calculations_enabled` TINYINT(1) DEFAULT 0,
    `transactions_enabled` TINYINT(1) DEFAULT 0,
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
    }
}
