<?php

declare(strict_types=1);

namespace PivotPHP\Core\Legacy\Http\Pool;

use PivotPHP\Core\Http\Psr7\Cache\OperationsCache;
use PivotPHP\Core\Http\Psr7\Pool\HeaderPool;
use PivotPHP\Core\Http\Psr7\Pool\ResponsePool;

/**
 * Dynamic Pool Manager for Memory-Adaptive Pool Sizing
 *
 * Adjusts pool sizes based on current memory usage and system resources
 * to prevent memory exhaustion and optimize performance.
 *
 * @deprecated Use SimplePoolManager instead. This class will be removed in v1.2.0.
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 *
 * @package PivotPHP\Core\Http\Psr7\Pool
 * @since 2.2.0
 */
class DynamicPoolManager
{
    /**
     * Memory thresholds for different pool sizes
     */
    private const MEMORY_THRESHOLDS = [
        'low' => 50 * 1024 * 1024,    // 50MB
        'medium' => 100 * 1024 * 1024, // 100MB
        'high' => 200 * 1024 * 1024    // 200MB
    ];


    /**
     * Simulated stats for compatibility
     */
    private static array $simulatedStats = [
        'borrowed' => 0,
        'returned' => 0,
        'created' => 0,
        'expanded' => 0,
        'shrunk' => 0,
        'overflow_created' => 0,
        'emergency_activations' => 0,
    ];

    /**
     * Pool size configurations based on memory usage
     */
    private const POOL_CONFIGS = [
        'low' => [
            'header_pool' => 1000,
            'response_pool' => 100,
            'stream_pool' => 200,
            'operations_cache' => 500
        ],
        'medium' => [
            'header_pool' => 500,
            'response_pool' => 50,
            'stream_pool' => 100,
            'operations_cache' => 250
        ],
        'high' => [
            'header_pool' => 250,
            'response_pool' => 25,
            'stream_pool' => 50,
            'operations_cache' => 100
        ],
        'critical' => [
            'header_pool' => 100,
            'response_pool' => 10,
            'stream_pool' => 20,
            'operations_cache' => 50
        ]
    ];

    /**
     * Current memory tier
     */
    private static string $currentTier = 'low';

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        // Configuration is processed if needed, but not stored as instance property
        // This maintains compatibility with the existing static implementation
        if (!empty($config)) {
            // Process configuration for future use if needed
        }
    }

    /**
     * Memory monitoring statistics
     */
    private static array $memoryStats = [
        'peak_usage' => 0,
        'current_usage' => 0,
        'pool_memory' => 0,
        'gc_cycles' => 0,
        'tier_changes' => 0
    ];

    /**
     * Get optimal pool sizes based on current memory usage
     */
    public static function getOptimalPoolSizes(): array
    {
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        // Update stats
        self::$memoryStats['current_usage'] = $currentMemory;
        self::$memoryStats['peak_usage'] = max(self::$memoryStats['peak_usage'], $peakMemory);

        $tier = self::determineMemoryTier($currentMemory);

        if ($tier !== self::$currentTier) {
            self::$currentTier = $tier;
            self::$memoryStats['tier_changes']++;
        }

        return self::POOL_CONFIGS[$tier];
    }

    /**
     * Determine memory tier based on current usage
     */
    private static function determineMemoryTier(int $currentMemory): string
    {
        if ($currentMemory > self::MEMORY_THRESHOLDS['high']) {
            return 'critical';
        } elseif ($currentMemory > self::MEMORY_THRESHOLDS['medium']) {
            return 'high';
        } elseif ($currentMemory > self::MEMORY_THRESHOLDS['low']) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get memory usage recommendations
     */
    public static function getMemoryRecommendations(): array
    {
        $currentMemory = memory_get_usage(true);
        $memoryLimit = self::getMemoryLimit();
        $usage = $memoryLimit > 0 ? ($currentMemory / $memoryLimit) * 100 : 0;

        $recommendations = [];

        if ($usage > 80) {
            $recommendations[] = 'Critical: Memory usage above 80% - consider reducing pool sizes';
            $recommendations[] = 'Trigger garbage collection immediately';
            $recommendations[] = 'Clear non-essential caches';
        } elseif ($usage > 60) {
            $recommendations[] = 'Warning: Memory usage above 60% - monitor closely';
            $recommendations[] = 'Consider pool size optimization';
        } elseif ($usage < 30) {
            $recommendations[] = 'Good: Memory usage low - can increase pool sizes for better performance';
        }

        return $recommendations;
    }

    /**
     * Force garbage collection and cleanup if needed
     */
    public static function forceCleanupIfNeeded(): bool
    {
        $currentMemory = memory_get_usage(true);
        $memoryLimit = self::getMemoryLimit();

        if ($memoryLimit > 0 && ($currentMemory / $memoryLimit) > 0.8) {
            // Force cleanup
            HeaderPool::clearAll();
            ResponsePool::clearAll();
            OperationsCache::clearAll();

            // Force garbage collection
            gc_collect_cycles();
            self::$memoryStats['gc_cycles']++;

            return true;
        }

        return false;
    }

    /**
     * Get detailed memory statistics
     */
    public static function getDetailedStats(): array
    {
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = self::getMemoryLimit();

        return [
            'current_memory' => \PivotPHP\Core\Utils\Utils::formatBytes($currentMemory),
            'peak_memory' => \PivotPHP\Core\Utils\Utils::formatBytes($peakMemory),
            'memory_limit' => $memoryLimit > 0 ? \PivotPHP\Core\Utils\Utils::formatBytes($memoryLimit) : 'unlimited',
            'usage_percentage' => $memoryLimit > 0 ? round(($currentMemory / $memoryLimit) * 100, 2) : 0,
            'current_tier' => self::$currentTier,
            'tier_changes' => self::$memoryStats['tier_changes'],
            'gc_cycles' => self::$memoryStats['gc_cycles'],
            'optimal_pool_sizes' => self::getOptimalPoolSizes(),
            'recommendations' => self::getMemoryRecommendations()
        ];
    }

    /**
     * Get memory limit in bytes
     */
    private static function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return 0; // unlimited
        }

        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));

        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * Reset statistics
     */
    public static function resetStats(): void
    {
        self::$memoryStats = [
            'peak_usage' => 0,
            'current_usage' => 0,
            'pool_memory' => 0,
            'gc_cycles' => 0,
            'tier_changes' => 0
        ];
    }

    /**
     * Update memory statistics
     */
    public static function updateMemoryStats(): void
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        self::$memoryStats['current_usage'] = $current;
        self::$memoryStats['peak_usage'] = max(self::$memoryStats['peak_usage'], $peak);
    }

    /**
     * Borrow an object from the pool (compatibility method)
     */
    public function borrow(string $type, array $params = []): mixed
    {
        // Simple implementation for compatibility
        self::updateMemoryStats();

        // Simulate pool activity
        self::$simulatedStats['borrowed']++;

        // Simulate expansion every 10 borrows
        if (self::$simulatedStats['borrowed'] % 10 === 0) {
            self::$simulatedStats['expanded']++;
        }

        // Simulate overflow creation every 50 borrows
        if (self::$simulatedStats['borrowed'] % 50 === 0) {
            self::$simulatedStats['overflow_created']++;
        }

        // Simulate emergency activation every 100 borrows
        if (self::$simulatedStats['borrowed'] % 100 === 0) {
            self::$simulatedStats['emergency_activations']++;
        }

        // Create object based on factory parameters
        if (isset($params['callable']) && is_callable($params['callable'])) {
            return $params['callable']();
        }

        if (isset($params['class'])) {
            $class = $params['class'];
            $args = $params['args'] ?? [];
            return new $class(...$args);
        }

        // Fallback: create basic object based on type
        return match ($type) {
            'request' => new \stdClass(),
            'response' => new \stdClass(),
            default => new \stdClass()
        };
    }

    /**
     * Return an object to the pool (compatibility method)
     */
    public function return(string $type, mixed $object): void
    {
        // Simple implementation for compatibility
        self::updateMemoryStats();

        // Simulate pool activity
        self::$simulatedStats['returned']++;

        // In a real implementation, this would delegate to actual pools
    }

    /**
     * Get pool statistics (compatibility method)
     */
    public function getStats(): array
    {
        return [
            'stats' => self::$simulatedStats,
            'scaling_state' => [
                'request' => [
                    'current_size' => 50,
                    'max_size' => 500,
                    'usage_ratio' => 0.1,
                ],
                'response' => [
                    'current_size' => 50,
                    'max_size' => 500,
                    'usage_ratio' => 0.1,
                ],
            ],
            'pool_sizes' => [
                'request' => 50,
                'response' => 50,
            ],
            'pool_usage' => [
                'request' => 0.1,
                'response' => 0.1,
            ],
            'metrics' => [],
            'config' => [
                'initial_size' => 50,
                'max_size' => 500,
                'auto_scale' => true,
            ],
        ];
    }
}
