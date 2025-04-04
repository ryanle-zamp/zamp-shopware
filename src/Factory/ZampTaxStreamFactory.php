<?php

namespace ZampTax\Factory;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ZampTaxStreamFactory
{
    public static function createStreamHandler(string $logBaseName, int $level): StreamHandler
    {
        $date = (new \DateTime())->format('Y-m-d');
        $logDir = dirname(__DIR__, 5) . '/var/log';
        $resolvedPath = $logDir . '/' . $logBaseName . '-' . $date . '.log';

        return new StreamHandler($resolvedPath, $level);
    }
}

