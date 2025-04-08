<?php

namespace ZampTax\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Context;
use Symfony\Component\Finder\Finder;
use Psr\Log\LoggerInterface;

class DeleteOldZampTaxLogsHandler extends ScheduledTaskHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getHandledMessages(): iterable
    {
        return [DeleteOldZampTaxLogs::class];
    }

    public function run(): void
    {
        $logDirectory = dirname(__DIR__, 5) . '/var/log';

        $finder = new Finder();
        $finder->files()
            ->in($logDirectory)
            ->name('/^ZampTax-\d{4}-\d{2}-\d{2}\.log$/')
            ->date('until 6 months ago');

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();

            if (is_file($filePath)) {
                try {
                    unlink($filePath);
                } catch (\Throwable $e) {
                    $this->logger->warning("Failed to delete file: $filePath", ['exception' => $e]);
                }
            }
        }
    }
}
