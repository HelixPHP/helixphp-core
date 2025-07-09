<?php

declare(strict_types=1);

namespace PivotPHP\Core\Pool\Distributed\Coordinators;

/**
 * No-operation coordinator for when distributed coordination is disabled
 *
 * This coordinator is used when no external coordination service is available.
 * It provides a stub implementation that allows the system to work in single-instance mode.
 */
class NoOpCoordinator implements CoordinatorInterface
{
    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        // Configuration not used in NoOpCoordinator - intentionally ignored
        unset($config); // Suppress unused parameter warning
    }

    /**
     * Connect to coordinator (no-op)
     */
    public function connect(): bool
    {
        return true;
    }

    /**
     * Disconnect from coordinator (no-op)
     */
    public function disconnect(): void
    {
        // No operation
    }

    /**
     * Set a value (no-op)
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        return true;
    }

    /**
     * Get a value (always returns null)
     */
    public function get(string $key): mixed
    {
        return null;
    }

    /**
     * Delete a key (no-op)
     */
    public function delete(string $key): bool
    {
        return true;
    }

    /**
     * Register an instance (no-op)
     */
    public function registerInstance(string $instanceId, array $data): bool
    {
        return true;
    }

    /**
     * Update instance information (no-op)
     */
    public function updateInstance(string $instanceId, array $data): bool
    {
        return true;
    }

    /**
     * Unregister an instance (no-op)
     */
    public function unregisterInstance(string $instanceId): bool
    {
        return true;
    }

    /**
     * Get all active instances (always returns empty array)
     */
    public function getActiveInstances(): array
    {
        return [];
    }

    /**
     * Push to queue (no-op)
     */
    public function push(string $key, array $data): bool
    {
        return true;
    }

    /**
     * Pop from queue (always returns null)
     */
    public function pop(string $key, int $timeout = 0): ?array
    {
        return null;
    }

    /**
     * Acquire leadership (always succeeds)
     */
    public function acquireLeadership(string $instanceId, int $ttl): bool
    {
        return true;
    }

    /**
     * Get current leader (always returns null)
     */
    public function getCurrentLeader(): ?string
    {
        return null;
    }

    /**
     * Release leadership (always succeeds)
     */
    public function releaseLeadership(string $instanceId): bool
    {
        return true;
    }

    /**
     * Check if connected (always true)
     */
    public function isConnected(): bool
    {
        return true;
    }

    /**
     * Get global pool size (always returns 0)
     */
    public function getGlobalPoolSize(): int
    {
        return 0;
    }
}
