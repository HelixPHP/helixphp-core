<?php

declare(strict_types=1);

namespace Helix\Http\Psr7\Pool;

use Helix\Http\Psr7\Cache\OperationsCache;

/**
 * Pool Manager for coordinating all object pools and caches
 *
 * @package Helix\Http\Psr7\Pool
 * @since 2.1.1
 */
class PoolManager
{
    /**
     * Whether pools have been initialized
     */
    private static bool $initialized = false;

    /**
     * Pool configuration
     */
    private static array $config = [
        'auto_warm_up' => true,
        'enable_response_pool' => true,
        'enable_header_pool' => true,
        'enable_operations_cache' => true,
        'max_memory_usage' => 50 * 1024 * 1024, // 50MB
    ];

    /**
     * Performance monitoring data
     */
    private static array $stats = [
        'pool_hits' => 0,
        'pool_misses' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
    ];

    /**
     * Initialize all pools and caches
     */
    public static function initialize(array $config = []): void
    {
        if (self::$initialized) {
            return;
        }

        self::$config = array_merge(self::$config, $config);

        if (self::$config['auto_warm_up']) {
            self::warmUpAllPools();
        }

        self::$initialized = true;
    }

    /**
     * Warm up all pools with common objects
     */
    public static function warmUpAllPools(): void
    {
        if (self::$config['enable_response_pool']) {
            ResponsePool::warmUp();
        }

        if (self::$config['enable_header_pool']) {
            HeaderPool::warmUp();
        }

        if (self::$config['enable_operations_cache']) {
            OperationsCache::warmUp();
        }
    }

    /**
     * Get comprehensive statistics from all pools
     */
    public static function getStats(): array
    {
        $stats = [
            'initialized' => self::$initialized,
            'config' => self::$config,
            'performance' => self::$stats,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];

        if (self::$config['enable_response_pool']) {
            $stats['response_pool'] = ResponsePool::getStats();
        }

        if (self::$config['enable_header_pool']) {
            $stats['header_pool'] = HeaderPool::getStats();
        }

        if (self::$config['enable_operations_cache']) {
            $stats['operations_cache'] = OperationsCache::getStats();
        }

        return $stats;
    }

    /**
     * Clear all pools and caches
     */
    public static function clearAll(): void
    {
        ResponsePool::clearAll();
        HeaderPool::clearAll();
        OperationsCache::clearAll();

        self::$stats = [
            'pool_hits' => 0,
            'pool_misses' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];
    }

    /**
     * Perform garbage collection on all pools
     */
    public static function garbageCollect(): array
    {
        $results = [
            'memory_before' => memory_get_usage(true),
            'objects_collected' => 0,
        ];

        // Force PHP garbage collection
        $collected = gc_collect_cycles();
        $results['objects_collected'] += $collected;

        // Pool-specific garbage collection
        if (self::$config['enable_response_pool']) {
            $results['response_objects_collected'] = ResponsePool::garbageCollect();
        }

        $results['memory_after'] = memory_get_usage(true);
        $results['memory_freed'] = $results['memory_before'] - $results['memory_after'];

        return $results;
    }

    /**
     * Check if memory usage is within limits
     */
    public static function checkMemoryUsage(): bool
    {
        $currentUsage = memory_get_usage(true);
        return $currentUsage <= self::$config['max_memory_usage'];
    }

    /**
     * Auto-manage pools based on memory usage
     */
    public static function autoManage(): void
    {
        if (!self::checkMemoryUsage()) {
            // Memory usage too high, perform cleanup
            self::garbageCollect();

            // If still high, clear some caches
            if (self::checkMemoryUsage() === false) {
                OperationsCache::clearAll();
                HeaderPool::clearAll();
            }
        }
    }

    /**
     * Record pool hit
     */
    public static function recordPoolHit(): void
    {
        self::$stats['pool_hits']++;
    }

    /**
     * Record pool miss
     */
    public static function recordPoolMiss(): void
    {
        self::$stats['pool_misses']++;
    }

    /**
     * Record cache hit
     */
    public static function recordCacheHit(): void
    {
        self::$stats['cache_hits']++;
    }

    /**
     * Record cache miss
     */
    public static function recordCacheMiss(): void
    {
        self::$stats['cache_misses']++;
    }

    /**
     * Get pool efficiency metrics
     */
    public static function getEfficiencyMetrics(): array
    {
        $totalHits = is_numeric(self::$stats['pool_hits']) ? self::$stats['pool_hits'] : 0;
        $totalHits += is_numeric(self::$stats['cache_hits']) ? self::$stats['cache_hits'] : 0;

        $totalMisses = is_numeric(self::$stats['pool_misses']) ? self::$stats['pool_misses'] : 0;
        $totalMisses += is_numeric(self::$stats['cache_misses']) ? self::$stats['cache_misses'] : 0;
        $totalRequests = $totalHits + $totalMisses;

        $hitRatio = $totalRequests > 0 ? (float)($totalHits / $totalRequests) * 100 : 0;

        return [
            'hit_ratio' => round($hitRatio, 2),
            'total_requests' => $totalRequests,
            'total_hits' => $totalHits,
            'total_misses' => $totalMisses,
            'pool_hit_ratio' => self::calculateRatio(self::$stats['pool_hits'], self::$stats['pool_misses']),
            'cache_hit_ratio' => self::calculateRatio(self::$stats['cache_hits'], self::$stats['cache_misses']),
        ];
    }

    /**
     * Calculate hit ratio percentage
     */
    private static function calculateRatio(int $hits, int $misses): float
    {
        $total = $hits + $misses;
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    /**
     * Get configuration
     */
    public static function getConfig(): array
    {
        return self::$config;
    }

    /**
     * Update configuration
     */
    public static function updateConfig(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Check if pools are initialized
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * Reset initialization state (for testing)
     */
    public static function reset(): void
    {
        self::$initialized = false;
        self::clearAll();
    }
}
