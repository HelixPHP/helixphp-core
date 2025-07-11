<?php

declare(strict_types=1);

namespace PivotPHP\Core\Performance;

use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Pool\DynamicPool;

/**
 * Real-time performance monitoring system
 */
class PerformanceMonitor
{
    /**
     * Monitoring configuration
     */
    private array $config = [
        'sample_rate' => 0.1,           // Sample 10% of requests
        'metric_window' => 60,          // 60 second window
        'percentiles' => [50, 90, 95, 99],
        'alert_thresholds' => [
            'latency_p99' => 1000,      // 1 second
            'error_rate' => 0.05,       // 5%
            'memory_usage' => 0.8,      // 80%
            'gc_frequency' => 100,      // per minute
        ],
        'export_interval' => 10,        // Export metrics every 10 seconds
    ];

    /**
     * Metrics storage
     */
    private array $metrics = [
        'requests' => [],
        'latencies' => [],
        'memory_samples' => [],
        'gc_events' => [],
        'pool_stats' => [],
        'errors' => [],
        'custom' => [],
    ];

    /**
     * Aggregated metrics
     */
    private array $aggregated = [];

    /**
     * Start times for request tracking
     */
    private array $activeRequests = [];

    /**
     * Alerts
     */
    private array $alerts = [];

    /**
     * Export callbacks
     */
    private array $exporters = [];

    /**
     * Last export time
     */
    private float $lastExportTime;

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->lastExportTime = microtime(true);

        // Register GC observer
        $this->registerGCObserver();
    }

    /**
     * Start monitoring a request
     */
    public function startRequest(string $requestId, array $metadata = []): void
    {
        if (!$this->shouldSample()) {
            return;
        }

        $this->activeRequests[$requestId] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'metadata' => $metadata,
        ];
    }

    /**
     * End monitoring a request
     */
    public function endRequest(
        string $requestId,
        int $statusCode,
        array $metadata = []
    ): void {
        if (!isset($this->activeRequests[$requestId])) {
            return;
        }

        $start = $this->activeRequests[$requestId];
        $now = microtime(true);

        // Calculate metrics
        $latency = ($now - $start['start_time']) * 1000; // ms
        $memoryDelta = memory_get_usage(true) - $start['start_memory'];

        // Record request
        $this->recordRequest(
            [
                'id' => $requestId,
                'timestamp' => $now,
                'latency' => $latency,
                'status_code' => $statusCode,
                'memory_delta' => $memoryDelta,
                'metadata' => array_merge($start['metadata'], $metadata),
            ]
        );

        // Clean up
        unset($this->activeRequests[$requestId]);

        // Check if should export
        $this->checkExport();
    }

    /**
     * Record a request
     */
    private function recordRequest(array $request): void
    {
        $window = $this->getCurrentWindow();

        // Store in time window
        if (!isset($this->metrics['requests'][$window])) {
            $this->metrics['requests'][$window] = [];
        }

        $this->metrics['requests'][$window][] = $request;
        $this->metrics['latencies'][] = $request['latency'];

        // Check for errors
        if ($request['status_code'] >= 500) {
            $this->recordError('server_error', $request);
        } elseif ($request['status_code'] >= 400) {
            $this->recordError('client_error', $request);
        }

        // Keep latencies bounded
        if (count($this->metrics['latencies']) > 10000) {
            array_shift($this->metrics['latencies']);
        }

        // Clean old windows
        $this->cleanOldWindows();
    }

    /**
     * Record an error
     */
    public function recordError(string $type, array $context = []): void
    {
        $window = $this->getCurrentWindow();

        if (!isset($this->metrics['errors'][$window])) {
            $this->metrics['errors'][$window] = [];
        }

        $this->metrics['errors'][$window][] = [
            'type' => $type,
            'timestamp' => microtime(true),
            'context' => $context,
        ];
    }

    /**
     * Record memory sample
     */
    public function recordMemorySample(): void
    {
        $this->metrics['memory_samples'][] = [
            'timestamp' => microtime(true),
            'usage' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'real_usage' => memory_get_usage(false),
        ];

        // Keep bounded
        if (count($this->metrics['memory_samples']) > 1000) {
            array_shift($this->metrics['memory_samples']);
        }
    }

    /**
     * Record pool statistics
     */
    public function recordPoolStats(array $stats): void
    {
        $this->metrics['pool_stats'][] = [
            'timestamp' => microtime(true),
            'stats' => $stats,
        ];

        // Keep bounded
        if (count($this->metrics['pool_stats']) > 100) {
            array_shift($this->metrics['pool_stats']);
        }
    }

    /**
     * Record custom metric
     */
    public function recordMetric(
        string $name,
        float $value,
        array $tags = []
    ): void {
        if (!isset($this->metrics['custom'][$name])) {
            $this->metrics['custom'][$name] = [];
        }

        $this->metrics['custom'][$name][] = [
            'timestamp' => microtime(true),
            'value' => $value,
            'tags' => $tags,
        ];

        // Keep bounded
        if (count($this->metrics['custom'][$name]) > 1000) {
            array_shift($this->metrics['custom'][$name]);
        }
    }

    /**
     * Get live metrics
     */
    public function getLiveMetrics(): array
    {
        $this->aggregate();

        return [
            'current_load' => $this->getCurrentLoad(),
            'pool_utilization' => $this->getPoolUtilization(),
            'memory_pressure' => $this->getMemoryPressure(),
            'gc_frequency' => $this->getGCFrequency(),
            'p99_latency' => $this->aggregated['latency_p99'] ?? 0,
            'error_rate' => $this->aggregated['error_rate'] ?? 0,
            'active_requests' => count($this->activeRequests),
            'alerts' => $this->alerts,
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $this->aggregate();

        return [
            'latency' => [
                'p50' => $this->aggregated['latency_p50'] ?? 0,
                'p90' => $this->aggregated['latency_p90'] ?? 0,
                'p95' => $this->aggregated['latency_p95'] ?? 0,
                'p99' => $this->aggregated['latency_p99'] ?? 0,
                'min' => $this->aggregated['latency_min'] ?? 0,
                'max' => $this->aggregated['latency_max'] ?? 0,
                'avg' => $this->aggregated['latency_avg'] ?? 0,
            ],
            'throughput' => [
                'rps' => $this->aggregated['requests_per_second'] ?? 0,
                'success_rate' => $this->aggregated['success_rate'] ?? 0,
                'error_rate' => $this->aggregated['error_rate'] ?? 0,
            ],
            'memory' => [
                'current' => $this->aggregated['memory_current'] ?? 0,
                'peak' => $this->aggregated['memory_peak'] ?? 0,
                'avg' => $this->aggregated['memory_avg'] ?? 0,
            ],
            'pool' => $this->getPoolMetrics(),
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    /**
     * Aggregate metrics
     */
    private function aggregate(): void
    {
        $now = microtime(true);

        // Latency percentiles
        if (!empty($this->metrics['latencies'])) {
            $sorted = $this->metrics['latencies'];
            sort($sorted);

            $this->aggregated['latency_min'] = min($sorted);
            $this->aggregated['latency_max'] = max($sorted);
            $this->aggregated['latency_avg'] = array_sum($sorted) / count($sorted);

            foreach ($this->config['percentiles'] as $p) {
                $index = (int) ceil(count($sorted) * ($p / 100)) - 1;
                $this->aggregated["latency_p$p"] = $sorted[$index] ?? 0;
            }
        }

        // Request rate
        $recentRequests = $this->getRecentRequests(60);
        $this->aggregated['requests_per_second'] = count($recentRequests) / 60;

        // Error rate
        $recentErrors = $this->getRecentErrors(60);
        $errorRate = count($recentRequests) > 0
            ? count($recentErrors) / count($recentRequests)
            : 0;
        $this->aggregated['error_rate'] = $errorRate;

        // Success rate
        $this->aggregated['success_rate'] = 1 - $errorRate;

        // Memory
        if (!empty($this->metrics['memory_samples'])) {
            $recent = array_slice($this->metrics['memory_samples'], -10);
            $usages = array_column($recent, 'usage');

            $this->aggregated['memory_current'] = end($usages);
            $this->aggregated['memory_peak'] = max($usages);
            $this->aggregated['memory_avg'] = array_sum($usages) / count($usages);
        }

        // Check alerts
        $this->checkAlerts();
    }

    /**
     * Get current load (requests per second)
     */
    private function getCurrentLoad(): float
    {
        $recentRequests = $this->getRecentRequests(10); // Last 10 seconds
        return count($recentRequests) / 10;
    }

    /**
     * Get pool utilization
     */
    private function getPoolUtilization(): float
    {
        if (empty($this->metrics['pool_stats'])) {
            return 0.0;
        }

        $latest = end($this->metrics['pool_stats']);
        $stats = $latest['stats'] ?? [];

        if (!isset($stats['pool_usage'])) {
            return 0.0;
        }

        $totalUsage = 0;
        $count = 0;

        foreach ($stats['pool_usage'] as $usage) {
            $totalUsage += $usage;
            $count++;
        }

        return $count > 0 ? $totalUsage / $count : 0.0;
    }

    /**
     * Get memory pressure
     */
    private function getMemoryPressure(): float
    {
        $limit = $this->getMemoryLimit();
        if ($limit <= 0) {
            return 0.0;
        }

        $current = memory_get_usage(true);
        return $current / $limit;
    }

    /**
     * Get memory limit
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        // Convert to bytes
        $value = (int) $limit;
        $unit = strtolower($limit[strlen($limit) - 1]);

        switch ($unit) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Get GC frequency
     */
    private function getGCFrequency(): float
    {
        $recentGC = array_filter(
            $this->metrics['gc_events'],
            fn($event) => $event['timestamp'] > microtime(true) - 60
        );

        return count($recentGC);
    }

    /**
     * Get recent requests
     */
    private function getRecentRequests(int $seconds): array
    {
        $cutoff = microtime(true) - $seconds;
        $recent = [];

        foreach ($this->metrics['requests'] as $window => $requests) {
            foreach ($requests as $request) {
                if ($request['timestamp'] > $cutoff) {
                    $recent[] = $request;
                }
            }
        }

        return $recent;
    }

    /**
     * Get recent errors
     */
    private function getRecentErrors(int $seconds): array
    {
        $cutoff = microtime(true) - $seconds;
        $recent = [];

        foreach ($this->metrics['errors'] as $window => $errors) {
            foreach ($errors as $error) {
                if ($error['timestamp'] > $cutoff) {
                    $recent[] = $error;
                }
            }
        }

        return $recent;
    }

    /**
     * Get pool metrics
     */
    private function getPoolMetrics(): array
    {
        try {
            $stats = OptimizedHttpFactory::getPoolStats();
            return [
                'sizes' => $stats['pool_sizes'] ?? [],
                'efficiency' => $stats['efficiency'] ?? [],
                'usage' => $stats['usage'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to get pool stats',
            ];
        }
    }

    /**
     * Check alerts
     */
    private function checkAlerts(): void
    {
        $this->alerts = [];

        // Latency alert
        if (($this->aggregated['latency_p99'] ?? 0) > $this->config['alert_thresholds']['latency_p99']) {
            $this->alerts[] = [
                'type' => 'latency',
                'severity' => 'warning',
                'message' => sprintf('P99 latency %.2fms exceeds threshold', $this->aggregated['latency_p99']),
            ];
        }

        // Error rate alert
        if (($this->aggregated['error_rate'] ?? 0) > $this->config['alert_thresholds']['error_rate']) {
            $this->alerts[] = [
                'type' => 'error_rate',
                'severity' => 'critical',
                'message' => sprintf('Error rate %.1f%% exceeds threshold', $this->aggregated['error_rate'] * 100),
            ];
        }

        // Memory alert
        $memoryPressure = $this->getMemoryPressure();
        if ($memoryPressure > $this->config['alert_thresholds']['memory_usage']) {
            $this->alerts[] = [
                'type' => 'memory',
                'severity' => 'warning',
                'message' => sprintf('Memory usage %.1f%% exceeds threshold', $memoryPressure * 100),
            ];
        }

        // GC frequency alert
        $gcFrequency = $this->getGCFrequency();
        if ($gcFrequency > $this->config['alert_thresholds']['gc_frequency']) {
            $this->alerts[] = [
                'type' => 'gc_frequency',
                'severity' => 'warning',
                'message' => sprintf('GC frequency %d/min exceeds threshold', $gcFrequency),
            ];
        }
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];

        // Based on latency
        if (($this->aggregated['latency_p99'] ?? 0) > 500) {
            $recommendations[] = 'High P99 latency detected - consider increasing pool sizes';
        }

        // Based on memory
        if ($this->getMemoryPressure() > 0.7) {
            $recommendations[] = 'High memory usage - enable more aggressive pooling';
        }

        // Based on error rate
        if (($this->aggregated['error_rate'] ?? 0) > 0.01) {
            $recommendations[] = 'Elevated error rate - check circuit breaker configuration';
        }

        // Based on pool efficiency
        $poolStats = $this->getPoolMetrics();
        if (isset($poolStats['efficiency'])) {
            foreach ($poolStats['efficiency'] as $type => $efficiency) {
                if ($efficiency < 50) {
                    $recommendations[] = "Low $type pool efficiency - consider adjusting pool size";
                }
            }
        }

        return $recommendations;
    }

    /**
     * Register GC observer
     */
    private function registerGCObserver(): void
    {
        // This would use a real GC observer in production
        // For now, we'll simulate with periodic checks
    }

    /**
     * Should sample this request?
     */
    private function shouldSample(): bool
    {
        return mt_rand() / mt_getrandmax() <= $this->config['sample_rate'];
    }

    /**
     * Get current window
     */
    private function getCurrentWindow(): int
    {
        return (int) floor(microtime(true) / $this->config['metric_window']);
    }

    /**
     * Clean old windows
     */
    private function cleanOldWindows(): void
    {
        $currentWindow = $this->getCurrentWindow();
        $maxAge = 5; // Keep 5 windows

        foreach (['requests', 'errors'] as $metric) {
            foreach ($this->metrics[$metric] as $window => $data) {
                if ($window < $currentWindow - $maxAge) {
                    unset($this->metrics[$metric][$window]);
                }
            }
        }
    }

    /**
     * Check if should export metrics
     */
    private function checkExport(): void
    {
        $now = microtime(true);

        if ($now - $this->lastExportTime >= $this->config['export_interval']) {
            $this->export();
            $this->lastExportTime = $now;
        }
    }

    /**
     * Register exporter
     */
    public function registerExporter(callable $exporter): void
    {
        $this->exporters[] = $exporter;
    }

    /**
     * Export metrics
     */
    public function export(): void
    {
        $metrics = $this->getExportMetrics();

        foreach ($this->exporters as $exporter) {
            try {
                $exporter($metrics);
            } catch (\Exception $e) {
                error_log("Failed to export metrics: " . $e->getMessage());
            }
        }
    }

    /**
     * Get metrics for export
     */
    private function getExportMetrics(): array
    {
        $this->aggregate();

        return [
            'timestamp' => microtime(true),
            'latency' => [
                'p50' => $this->aggregated['latency_p50'] ?? 0,
                'p90' => $this->aggregated['latency_p90'] ?? 0,
                'p95' => $this->aggregated['latency_p95'] ?? 0,
                'p99' => $this->aggregated['latency_p99'] ?? 0,
            ],
            'throughput' => [
                'rps' => $this->aggregated['requests_per_second'] ?? 0,
                'error_rate' => $this->aggregated['error_rate'] ?? 0,
            ],
            'memory' => [
                'usage' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'pressure' => $this->getMemoryPressure(),
            ],
            'pool' => $this->getPoolMetrics(),
            'alerts' => $this->alerts,
        ];
    }
}
