<?php

declare(strict_types=1);

namespace PivotPHP\Core\Performance;

/**
 * Performance Monitor
 *
 * Simple and effective performance monitoring for the microframework.
 * Provides basic metrics without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class PerformanceMonitor
{
    /**
     * Simple metrics storage
     */
    private static array $metrics = [
        'requests_total' => 0,
        'requests_success' => 0,
        'requests_error' => 0,
        'start_time' => 0,
        'peak_memory' => 0,
    ];

    /**
     * Initialize monitor
     */
    public static function init(): void
    {
        self::$metrics['start_time'] = microtime(true);
        self::$metrics['peak_memory'] = memory_get_peak_usage(true);
    }

    /**
     * Record a request
     */
    public static function recordRequest(bool $success = true): void
    {
        self::$metrics['requests_total']++;

        if ($success) {
            self::$metrics['requests_success']++;
        } else {
            self::$metrics['requests_error']++;
        }

        // Update peak memory
        self::$metrics['peak_memory'] = max(
            self::$metrics['peak_memory'],
            memory_get_peak_usage(true)
        );
    }

    /**
     * Get simple metrics
     */
    public static function getMetrics(): array
    {
        $uptime = self::$metrics['start_time'] > 0
            ? microtime(true) - self::$metrics['start_time']
            : 0;

        $errorRate = self::$metrics['requests_total'] > 0
            ? (self::$metrics['requests_error'] / self::$metrics['requests_total']) * 100
            : 0;

        $requestsPerSecond = $uptime > 0
            ? self::$metrics['requests_total'] / $uptime
            : 0;

        return [
            'uptime' => round($uptime, 2),
            'requests_total' => self::$metrics['requests_total'],
            'requests_success' => self::$metrics['requests_success'],
            'requests_error' => self::$metrics['requests_error'],
            'error_rate' => round($errorRate, 2),
            'requests_per_second' => round($requestsPerSecond, 2),
            'memory_current' => self::formatBytes(memory_get_usage(true)),
            'memory_peak' => self::formatBytes(self::$metrics['peak_memory']),
        ];
    }

    /**
     * Reset metrics
     */
    public static function reset(): void
    {
        self::$metrics = [
            'requests_total' => 0,
            'requests_success' => 0,
            'requests_error' => 0,
            'start_time' => microtime(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Get status summary
     */
    public static function getStatus(): array
    {
        $metrics = self::getMetrics();

        return [
            'status' => $metrics['error_rate'] < 5 ? 'healthy' : 'degraded',
            'uptime' => $metrics['uptime'],
            'requests_per_second' => $metrics['requests_per_second'],
            'error_rate' => $metrics['error_rate'],
            'memory_usage' => $metrics['memory_current'],
        ];
    }

    /**
     * Format bytes for human readability
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
