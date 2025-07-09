<?php

declare(strict_types=1);

namespace PivotPHP\Core\Pool\Distributed\Coordinators;

/**
 * Interface for distributed coordination backends
 */
interface CoordinatorInterface
{
    /**
     * Register an instance
     */
    public function registerInstance(string $instanceId, array $data): bool;

    /**
     * Update instance information
     */
    public function updateInstance(string $instanceId, array $data): bool;

    /**
     * Unregister an instance
     */
    public function unregisterInstance(string $instanceId): bool;

    /**
     * Get all active instances
     */
    public function getActiveInstances(): array;

    /**
     * Push data to a queue
     */
    public function push(string $key, array $data): bool;

    /**
     * Pop data from a queue
     */
    public function pop(string $key, int $timeout = 0): ?array;

    /**
     * Acquire leadership
     */
    public function acquireLeadership(string $instanceId, int $ttl): bool;

    /**
     * Release leadership
     */
    public function releaseLeadership(string $instanceId): bool;

    /**
     * Get current leader
     */
    public function getCurrentLeader(): ?string;

    /**
     * Get global pool size
     */
    public function getGlobalPoolSize(): int;

    /**
     * Set a value with TTL
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool;

    /**
     * Get a value
     */
    public function get(string $key): mixed;

    /**
     * Delete a key
     */
    public function delete(string $key): bool;

    /**
     * Check if connected
     */
    public function isConnected(): bool;
}
