<?php

namespace ZampTax\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class DeleteOldZampTaxLogs extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'zamp_tax.delete_old_logs';
    }

    public static function getDefaultInterval(): int
    {
        return 86400;
    }
}
