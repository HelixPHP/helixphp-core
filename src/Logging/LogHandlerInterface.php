<?php

namespace Express\Logging;

/**
 * Interface para handlers de log
 */
interface LogHandlerInterface
{
    public function handle(array $record): void;
}
