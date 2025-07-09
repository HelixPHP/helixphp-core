<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Pool\Strategies;

/**
 * Interface for pool overflow strategies
 */
interface OverflowStrategy
{
    /**
     * Check if this strategy can handle the current situation
     */
    public function canHandle(string $type, array $context): bool;

    /**
     * Handle the overflow situation
     */
    public function handle(string $type, array $params): mixed;

    /**
     * Get strategy metrics
     */
    public function getMetrics(): array;
}
