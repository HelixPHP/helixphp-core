<?php

declare(strict_types=1);

namespace Express\Monitoring;

use Express\Utils\Utils;
use Express\Http\Psr7\Pool\HeaderPool;
use Express\Http\Psr7\Pool\ResponsePool;
use Express\Http\Psr7\Pool\DynamicPoolManager;
use Express\Http\Psr7\Pool\EnhancedStreamPool;
use Express\Http\Psr7\Cache\OperationsCache;
use Express\Http\Psr7\Cache\IntelligentJsonCache;
use Express\Routing\RouteCache;

/**
 * Real-time Performance Monitor
 *
 * Provides comprehensive monitoring of all optimization components
 * with alerts and recommendations for performance tuning.
 *
 * @package Express\Monitoring
 * @since 2.2.0
 */
class PerformanceMonitor
{
    /**
     * Monitoring configuration
     * @var array<string, mixed>
     */
    private static array $config = [
        'enable_alerts' => true,
        'memory_threshold' => 80, // Percentage
        'hit_rate_threshold' => 70, // Percentage
        'gc_threshold' => 100, // GC cycles
        'alert_cooldown' => 300, // 5 minutes
    ];

    /**
     * Alert history to prevent spam
     * @var array<string, mixed>
     */
    private static array $alertHistory = [];

    /**
     * Performance baseline for comparison
     * @var array<string, mixed>
     */
    private static array $baseline = [];

    /**
     * Monitoring metrics collection
     * @var array<string, mixed>
     */
    private static array $metrics = [
        'start_time' => 0,
        'request_count' => 0,
        'total_memory_saved' => 0,
        'performance_issues' => 0
    ];

    /**
     * Initialize monitoring
     * @param array<string, mixed> $config
     */
    public static function initialize(array $config = []): void
    {
        self::$config = array_merge(self::$config, $config);
        self::$metrics['start_time'] = microtime(true);

        // Establish baseline
        self::recordBaseline();
    }

    /**
     * Get comprehensive performance dashboard
     * @return array<string, mixed>
     */
    public static function getDashboard(): array
    {
        $uptime = microtime(true) - self::$metrics['start_time'];

        return [
            'system_info' => self::getSystemInfo($uptime),
            'pool_status' => self::getPoolStatus(),
            'cache_status' => self::getCacheStatus(),
            'memory_analysis' => self::getMemoryAnalysis(),
            'performance_alerts' => self::checkPerformanceAlerts(),
            'recommendations' => self::getRecommendations(),
            'trends' => self::getTrends()
        ];
    }

    /**
     * Get system information
     * @return array<string, mixed>
     */
    private static function getSystemInfo(float $uptime): array
    {
        return [
            'uptime_seconds' => round($uptime, 2),
            'uptime_formatted' => self::formatUptime($uptime),
            'requests_processed' => self::$metrics['request_count'],
            'requests_per_second' => self::$metrics['request_count'] / max($uptime, 1),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'current_memory' => Utils::formatBytes(memory_get_usage(true)),
            'peak_memory' => Utils::formatBytes(memory_get_peak_usage(true)),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false
        ];
    }

    /**
     * Get pool status for all components
     * @return array<string, mixed>
     */
    private static function getPoolStatus(): array
    {
        $status = [];

        // Header Pool
        if (class_exists(HeaderPool::class)) {
            $headerStats = HeaderPool::getDetailedMetrics();
            $status['header_pool'] = [
                'status' => $headerStats['cache_hit_rate'] > self::$config['hit_rate_threshold']
                    ? 'optimal'
                    : 'needs_attention',
                'hit_rate' => $headerStats['cache_hit_rate'],
                'total_items' => array_sum($headerStats['current_pool_sizes']),
                'memory_saved' => $headerStats['memory_efficiency']['estimated_memory_saved']
            ];
        }

        // Stream Pool
        if (class_exists(EnhancedStreamPool::class)) {
            $streamStats = EnhancedStreamPool::getStats();
            $status['stream_pool'] = [
                'status' => $streamStats['hit_rate'] > self::$config['hit_rate_threshold']
                    ? 'optimal'
                    : 'needs_attention',
                'hit_rate' => $streamStats['hit_rate'],
                'total_items' => array_sum($streamStats['pool_sizes']),
                'memory_usage' => $streamStats['memory_usage']
            ];
        }

        // Response Pool
        if (class_exists(ResponsePool::class)) {
            $responseStats = ResponsePool::getStats();
            $isOptimal = isset($responseStats['hit_rate'])
                && $responseStats['hit_rate'] > self::$config['hit_rate_threshold'];
            $status['response_pool'] = [
                'status' => $isOptimal ? 'optimal' : 'active',
                'pool_size' => $responseStats['pool_size'] ?? 0,
                'active_objects' => $responseStats['active_objects'] ?? 0
            ];
        }

        return $status;
    }

    /**
     * Get cache status for all caching components
     * @return array<string, mixed>
     */
    private static function getCacheStatus(): array
    {
        $status = [];

        // Operations Cache
        if (class_exists(OperationsCache::class)) {
            $opStats = OperationsCache::getStats();
            $status['operations_cache'] = [
                'status' => 'active',
                'cache_sizes' => $opStats['cache_sizes'] ?? [],
                'hit_rates' => $opStats['hit_rates'] ?? []
            ];
        }

        // JSON Cache
        if (class_exists(IntelligentJsonCache::class)) {
            $jsonStats = IntelligentJsonCache::getStats();
            $status['json_cache'] = [
                'status' => 'active',
                'template_hit_rate' => $jsonStats['template_hit_rate'],
                'direct_hit_rate' => $jsonStats['direct_hit_rate'],
                'templates_created' => $jsonStats['templates_created'],
                'memory_saved' => $jsonStats['memory_saved']
            ];
        }

        // Route Cache
        if (class_exists(RouteCache::class)) {
            $routeStats = RouteCache::getStats();
            $status['route_cache'] = [
                'status' => 'active',
                'hit_rate' => $routeStats['hit_rate_percentage'] ?? 0,
                'cached_routes' => $routeStats['cached_routes'] ?? 0,
                'memory_usage' => $routeStats['memory_usage'] ?? '0 B'
            ];
        }

        return $status;
    }

    /**
     * Analyze memory usage patterns
     * @return array<string, mixed>
     */
    private static function getMemoryAnalysis(): array
    {
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = self::getMemoryLimitBytes();

        $usagePercent = $memoryLimit > 0 ? ($currentMemory / $memoryLimit) * 100 : 0;

        // Get dynamic pool manager stats if available
        $poolManagerStats = [];
        if (class_exists(DynamicPoolManager::class)) {
            $poolManagerStats = DynamicPoolManager::getDetailedStats();
        }

        return [
            'current_usage' => Utils::formatBytes($currentMemory),
            'peak_usage' => Utils::formatBytes($peakMemory),
            'usage_percentage' => round($usagePercent, 2),
            'memory_limit' => $memoryLimit > 0 ? Utils::formatBytes($memoryLimit) : 'unlimited',
            'status' => self::getMemoryStatus($usagePercent),
            'pool_manager' => $poolManagerStats,
            'gc_stats' => gc_status()
        ];
    }

    /**
     * Check for performance alerts
     * @return array<array<string, mixed>>
     */
    private static function checkPerformanceAlerts(): array
    {
        $alerts = [];
        $currentTime = time();

        // Memory usage alert
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = self::getMemoryLimitBytes();

        if ($memoryLimit > 0) {
            $usagePercent = ($memoryUsage / $memoryLimit) * 100;

            if ($usagePercent > self::$config['memory_threshold']) {
                $alerts[] = [
                    'type' => 'memory_high',
                    'severity' => $usagePercent > 90 ? 'critical' : 'warning',
                    'message' => "Memory usage at {$usagePercent}%",
                    'recommendation' => 'Consider reducing pool sizes or clearing caches'
                ];
            }
        }

        // Pool hit rate alerts
        if (class_exists(HeaderPool::class)) {
            $headerMetrics = HeaderPool::getDetailedMetrics();
            if ($headerMetrics['cache_hit_rate'] < self::$config['hit_rate_threshold']) {
                $alerts[] = [
                    'type' => 'low_hit_rate',
                    'severity' => 'warning',
                    'message' => "Header pool hit rate low: {$headerMetrics['cache_hit_rate']}%",
                    'recommendation' => 'Consider warming up cache with common headers'
                ];
            }
        }

        // GC pressure alert
        $gcStats = gc_status();
        if ($gcStats['runs'] > self::$config['gc_threshold']) {
            $alerts[] = [
                'type' => 'gc_pressure',
                'severity' => 'info',
                'message' => "High GC activity: {$gcStats['runs']} runs",
                'recommendation' => 'Monitor memory usage patterns'
            ];
        }

        return $alerts;
    }

    /**
     * Get performance recommendations
     * @return array<array<string, mixed>>
     */
    private static function getRecommendations(): array
    {
        $recommendations = [];

        // Analyze pool performance
        $poolStatus = self::getPoolStatus();

        foreach ($poolStatus as $poolName => $status) {
            if (
                is_array($status) &&
                isset($status['hit_rate']) &&
                is_numeric($status['hit_rate']) &&
                $status['hit_rate'] < 80
            ) {
                $hitRate = (float) $status['hit_rate'];
                $recommendations[] = [
                    'category' => 'pool_optimization',
                    'priority' => 'medium',
                    'message' => "Optimize {$poolName} - hit rate {$hitRate}%",
                    'action' => 'Consider warming up cache or adjusting pool sizes'
                ];
            }
        }

        // Memory optimization recommendations
        $memoryAnalysis = self::getMemoryAnalysis();
        if ($memoryAnalysis['usage_percentage'] > 70) {
            $recommendations[] = [
                'category' => 'memory_optimization',
                'priority' => 'high',
                'message' => 'High memory usage detected',
                'action' => 'Consider enabling dynamic pool sizing or reducing cache sizes'
            ];
        }

        // Performance tuning
        if (self::$metrics['request_count'] > 1000) {
            $uptime = microtime(true) - self::$metrics['start_time'];
            $rps = self::$metrics['request_count'] / $uptime;

            if ($rps > 100) {
                $recommendations[] = [
                    'category' => 'high_load',
                    'priority' => 'info',
                    'message' => "High request rate: {$rps} req/s",
                    'action' => 'Monitor pool efficiency and consider precompilation'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get performance trends
     * @return array<string, mixed>
     */
    private static function getTrends(): array
    {
        // Simple trend analysis
        $uptime = microtime(true) - self::$metrics['start_time'];

        return [
            'uptime_hours' => round($uptime / 3600, 2),
            'avg_requests_per_hour' => round((self::$metrics['request_count'] / max($uptime, 1)) * 3600, 0),
            'memory_trend' => 'stable', // Could be enhanced with historical data
            'performance_trend' => 'stable'
        ];
    }

    /**
     * Record performance baseline
     */
    private static function recordBaseline(): void
    {
        self::$baseline = [
            'memory_usage' => memory_get_usage(true),
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Increment request counter
     */
    public static function recordRequest(): void
    {
        self::$metrics['request_count']++;
    }

    /**
     * Get alert history for monitoring dashboard
     *
     * @return array<string, mixed>
     */
    public static function getAlertHistory(): array
    {
        return self::$alertHistory;
    }

    /**
     * Get memory status based on usage percentage
     */
    private static function getMemoryStatus(float $usagePercent): string
    {
        if ($usagePercent > 90) {
            return 'critical';
        } elseif ($usagePercent > 70) {
            return 'warning';
        } elseif ($usagePercent > 50) {
            return 'moderate';
        } else {
            return 'optimal';
        }
    }

    /**
     * Get memory limit in bytes
     */
    private static function getMemoryLimitBytes(): int
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
     * Format uptime to human readable format
     */
    private static function formatUptime(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds, 1) . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . 'm';
        } elseif ($seconds < 86400) {
            return round($seconds / 3600, 1) . 'h';
        } else {
            return round($seconds / 86400, 1) . 'd';
        }
    }

    /**
     * Export monitoring data for external analysis
     * @return array<string, mixed>
     */
    public static function exportMetrics(): array
    {
        return [
            'timestamp' => time(),
            'config' => self::$config,
            'metrics' => self::$metrics,
            'baseline' => self::$baseline,
            'dashboard' => self::getDashboard()
        ];
    }
}
