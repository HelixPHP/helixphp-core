<?php

namespace Helix\Logging;

/**
 * Interface para handlers de log
 */
interface LogHandlerInterface
{
    /** @param array<string, mixed> $record */
    public function handle(array $record): void;
}
