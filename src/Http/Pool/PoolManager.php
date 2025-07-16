<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Pool;

/**
 * Pool Manager
 *
 * Simple and effective object pooling for the microframework.
 * Provides basic pooling functionality without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class PoolManager
{
    /**
     * Pool storage
     */
    private array $pools = [];

    /**
     * Simple configuration
     */
    private int $maxPoolSize = 50;
    private bool $enabled = true;

    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Constructor for singleton (public for testing)
     */
    public function __construct(array $config = [])
    {
        // Initialize basic pools
        $this->pools = [
            'request' => [],
            'response' => [],
            'stream' => [],
        ];
        
        // Apply configuration
        if (isset($config['maxPoolSize'])) {
            $this->maxPoolSize = $config['maxPoolSize'];
        }
        if (isset($config['enabled'])) {
            $this->enabled = $config['enabled'];
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Enable pooling
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable pooling
     */
    public function disable(): void
    {
        $this->enabled = false;
        $this->clearAll();
    }

    /**
     * Set maximum pool size
     */
    public function setMaxPoolSize(int $size): void
    {
        $this->maxPoolSize = max(1, $size);
    }

    /**
     * Rent an object from pool
     */
    public function rent(string $poolName): ?object
    {
        if (!$this->enabled || empty($this->pools[$poolName])) {
            return null;
        }

        return array_pop($this->pools[$poolName]);
    }

    /**
     * Return an object to pool
     */
    public function return(string $poolName, object $object): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->pools[$poolName])) {
            $this->pools[$poolName] = [];
        }

        // Don't exceed max pool size
        if (count($this->pools[$poolName]) < $this->maxPoolSize) {
            $this->pools[$poolName][] = $object;
        }
    }

    /**
     * Clear all pools
     */
    public function clearAll(): void
    {
        $this->pools = [
            'request' => [],
            'response' => [],
            'stream' => [],
        ];
    }

    /**
     * Clear specific pool
     */
    public function clearPool(string $poolName): void
    {
        if (isset($this->pools[$poolName])) {
            $this->pools[$poolName] = [];
        }
    }

    /**
     * Get simple pool statistics
     */
    public function getStats(): array
    {
        $stats = [
            'enabled' => $this->enabled,
            'max_pool_size' => $this->maxPoolSize,
            'pools' => [],
            'stats' => [
                'total_operations' => 0,
                'pool_hits' => 0,
                'pool_misses' => 0,
            ],
            'scaling_state' => 'normal',
        ];

        foreach ($this->pools as $name => $pool) {
            $stats['pools'][$name] = [
                'size' => count($pool),
                'utilization' => count($pool) / $this->maxPoolSize,
            ];
        }

        return $stats;
    }

    /**
     * Reset pool statistics
     */
    public function resetStats(): void
    {
        // Simple implementation - just clear all pools
        $this->clearAll();
    }

    /**
     * Reset pool statistics (static version)
     */
    public static function resetStatsStatic(): void
    {
        $instance = self::getInstance();
        $instance->resetStats();
    }

    /**
     * Get pool size
     */
    public function getPoolSize(string $poolName): int
    {
        return count($this->pools[$poolName] ?? []);
    }

    /**
     * Check if pool exists
     */
    public function hasPool(string $poolName): bool
    {
        return isset($this->pools[$poolName]);
    }

    /**
     * Get optimal pool sizes (simplified)
     */
    public function getOptimalPoolSizes(): array
    {
        return [
            'request' => $this->maxPoolSize,
            'response' => $this->maxPoolSize,
            'stream' => $this->maxPoolSize,
        ];
    }

    /**
     * Get memory recommendations (simplified)
     */
    public function getMemoryRecommendations(): array
    {
        return [
            'current_usage' => 'normal',
            'recommended_action' => 'none',
            'tier' => 'normal',
        ];
    }

    /**
     * Get detailed statistics
     */
    public function getDetailedStats(): array
    {
        $stats = $this->getStats();
        $stats['detailed'] = true;
        $stats['memory_usage'] = memory_get_usage(true);
        $stats['memory_tier'] = 'normal';
        return $stats;
    }

    /**
     * Force cleanup if needed
     */
    public function forceCleanupIfNeeded(): void
    {
        // Simple implementation - clear if too many items
        foreach ($this->pools as $poolName => $pool) {
            if (count($pool) > $this->maxPoolSize) {
                $this->clearPool($poolName);
            }
        }
    }

    /**
     * Borrow object from pool (alias for rent)
     */
    public function borrow(string $poolName): ?object
    {
        return $this->rent($poolName);
    }
}
