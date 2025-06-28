<?php

declare(strict_types=1);

namespace Express\Http\Psr7\Pool;

/**
 * Dynamic Pool Manager for Memory-Adaptive Pool Sizing
 *
 * Adjusts pool sizes based on current memory usage and system resources
 * to prevent memory exhaustion and optimize performance.
 *
 * @package Express\Http\Psr7\Pool
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
            ResponsePool::cleanup();
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
            'current_memory' => self::formatBytes($currentMemory),
            'peak_memory' => self::formatBytes($peakMemory),
            'memory_limit' => $memoryLimit > 0 ? self::formatBytes($memoryLimit) : 'unlimited',
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
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
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
}
