<?php

namespace ZampTax\ScheduledTask;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Shopware\Core\Framework\MessageQueue\Handler\ScheduledTaskHandler;
use Shopware\Core\Framework\Context;
use Symfony\Component\Finder\Finder;

#[AsMessageHandler]
class DeleteOldZampTaxLogsHandler extends ScheduledTaskHandler
{
    public static function getHandledMessages(): iterable
    {
        return [DeleteOldZampTaxLogs::class];
    }

    public function run(): void
    {
        $logDirectory = dirname(__DIR__, 5) . '/var/log'; // Adjust path if needed

        $finder = new Finder();
        $finder->files()
            ->in($logDirectory)
            ->name('/^ZampTax-\d{4}-\d{2}-\d{2}\.log$/')
            ->date('until 6 months ago');

        foreach ($finder as $file) {
            @unlink($file->getRealPath());
        }
    }
}
