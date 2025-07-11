<?php

namespace PivotPHP\Core\Logging;

/**
 * Interface para handlers de log
 */
interface LogHandlerInterface
{
    /**
     * Handle a log record
     *
     * @param array<string, mixed> $record The log record to handle
     */
    public function handle(array $record): void;
}
