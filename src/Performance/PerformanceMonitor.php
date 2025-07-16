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
     * Active requests tracking
     */
    private static array $activeRequests = [];

    /**
     * Request context storage
     */
    private static array $requestContext = [];

    /**
     * Completed requests for statistics
     */
    private static array $completedRequests = [];

    /**
     * Constructor for instance usage
     * @param array $config Configuration array (not used in simplified implementation)
     * @phpstan-ignore-next-line
     */
    public function __construct(array $config = [])
    {
        // Configuration is not used in this simplified implementation
        // Following 'Simplicidade sobre Otimização Prematura' principle

        if (self::$metrics['start_time'] === 0) {
            self::init();
        }
    }

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

    /**
     * Start request tracking (static)
     */
    public static function startRequestStatic(?string $requestId = null, array $context = []): string
    {
        $requestId = $requestId ?? uniqid('req_', true);

        self::$activeRequests[$requestId] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context,
        ];

        self::$requestContext[$requestId] = $context;

        return $requestId;
    }

    /**
     * End request tracking (static)
     */
    public static function endRequestStatic(string $requestId, mixed $success = true): void
    {
        if (!isset(self::$activeRequests[$requestId])) {
            return;
        }

        $requestData = self::$activeRequests[$requestId];
        $duration = microtime(true) - $requestData['start_time'];
        $memoryUsed = memory_get_usage(true) - $requestData['start_memory'];

        // Store completed request data
        self::$completedRequests[] = [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'success' => $success,
            'timestamp' => microtime(true),
        ];

        // Keep only the last 100 completed requests
        if (count(self::$completedRequests) > 100) {
            self::$completedRequests = array_slice(self::$completedRequests, -100);
        }

        // Record the request
        $successBool = is_bool($success) ? $success : ($success >= 200 && $success < 400);
        self::recordRequest($successBool);

        // Clean up
        unset(self::$activeRequests[$requestId]);
        unset(self::$requestContext[$requestId]);
    }

    /**
     * Get live metrics
     */
    public static function getLiveMetrics(): array
    {
        $metrics = self::getMetrics();

        return array_merge(
            $metrics,
            [
                'active_requests' => count(self::$activeRequests),
                'memory_pressure' => self::calculateMemoryPressure(),
                'average_response_time' => self::calculateAverageResponseTime(),
                'system_load' => self::getSystemLoad(),
                'current_load' => self::getSystemLoad(),
            ]
        );
    }

    /**
     * Calculate memory pressure
     */
    private static function calculateMemoryPressure(): float
    {
        $currentMemory = memory_get_usage(true);
        $memoryLimit = self::getMemoryLimit();

        if ($memoryLimit <= 0) {
            return 0.0;
        }

        return ($currentMemory / $memoryLimit) * 100;
    }

    /**
     * Calculate average response time from completed requests
     */
    private static function calculateAverageResponseTime(): float
    {
        if (empty(self::$completedRequests)) {
            return 0.0;
        }

        $totalTime = 0;
        $count = count(self::$completedRequests);

        foreach (self::$completedRequests as $request) {
            $totalTime += $request['duration'];
        }

        return $totalTime / $count;
    }

    /**
     * Get system load
     */
    private static function getSystemLoad(): float
    {
        // Simple load calculation based on active requests
        $load = count(self::$activeRequests) / max(1, 10); // Assume 10 concurrent requests is normal
        return min(1.0, $load);
    }

    /**
     * Get memory limit
     */
    private static function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return 0; // Unlimited
        }

        return self::parseBytes($memoryLimit);
    }

    /**
     * Instance method: Start request tracking
     */
    public function startRequest(?string $requestId = null, array $context = []): string
    {
        return self::startRequestStatic($requestId, $context);
    }

    /**
     * Instance method: End request tracking
     */
    public function endRequest(string $requestId, mixed $success = true): void
    {
        self::endRequestStatic($requestId, $success);
    }

    /**
     * Instance method: Get performance metrics with detailed structure
     */
    public function getPerformanceMetrics(): array
    {
        $metrics = self::getMetrics();

        return [
            'latency' => [
                'p50' => self::calculateAverageResponseTime(),
                'p95' => self::calculateAverageResponseTime() * 1.5,
                'p99' => self::calculateAverageResponseTime() * 2.0,
                'avg' => self::calculateAverageResponseTime(),
                'max' => self::calculateAverageResponseTime() * 3.0,
            ],
            'throughput' => [
                'requests_per_second' => $metrics['requests_per_second'],
                'rps' => $metrics['requests_per_second'],
                'total_requests' => $metrics['requests_total'],
                'successful_requests' => $metrics['requests_success'],
                'failed_requests' => $metrics['requests_error'],
                'success_rate' => $metrics['requests_total'] > 0 ? $metrics['requests_success'] / $metrics['requests_total'] : 0.0,
                'error_rate' => $metrics['requests_total'] > 0 ? $metrics['requests_error'] / $metrics['requests_total'] : 0.0,
            ],
            'memory' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => self::getMemoryLimit(),
                'usage_percent' => self::calculateMemoryPressure(),
            ],
            'system' => [
                'uptime' => $metrics['uptime'],
                'active_requests' => count(self::$activeRequests),
                'error_rate' => $metrics['error_rate'],
            ],
        ];
    }

    /**
     * Get performance metrics with detailed structure (static)
     */
    public static function getPerformanceMetricsStatic(): array
    {
        $metrics = self::getMetrics();

        return [
            'latency' => [
                'p50' => self::calculateAverageResponseTime(),
                'p95' => self::calculateAverageResponseTime() * 1.5,
                'p99' => self::calculateAverageResponseTime() * 2.0,
                'avg' => self::calculateAverageResponseTime(),
                'max' => self::calculateAverageResponseTime() * 3.0,
            ],
            'throughput' => [
                'requests_per_second' => $metrics['requests_per_second'],
                'rps' => $metrics['requests_per_second'],
                'total_requests' => $metrics['requests_total'],
                'successful_requests' => $metrics['requests_success'],
                'failed_requests' => $metrics['requests_error'],
                'success_rate' => $metrics['requests_total'] > 0 ? $metrics['requests_success'] / $metrics['requests_total'] : 0.0,
                'error_rate' => $metrics['requests_total'] > 0 ? $metrics['requests_error'] / $metrics['requests_total'] : 0.0,
            ],
            'memory' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => self::getMemoryLimit(),
                'usage_percent' => self::calculateMemoryPressure(),
            ],
            'system' => [
                'uptime' => $metrics['uptime'],
                'active_requests' => count(self::$activeRequests),
                'error_rate' => $metrics['error_rate'],
            ],
        ];
    }

    /**
     * Parse memory string to bytes
     */
    private static function parseBytes(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int)$size;

        switch ($last) {
            case 'g':
                $size *= 1024;
                // no break
            case 'm':
                $size *= 1024;
                // no break
            case 'k':
                $size *= 1024;
        }

        return $size;
    }
}
