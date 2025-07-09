<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Pool;

/**
 * Pool metrics collector and analyzer
 */
class PoolMetrics
{
    /**
     * Metrics data
     */
    private array $metrics = [
        'borrows' => [],
        'returns' => [],
        'expansions' => [],
        'shrinks' => [],
        'emergency_activations' => [],
        'performance' => [],
    ];

    /**
     * Time series data
     */
    private array $timeSeries = [];

    /**
     * Current window start time
     */
    private int $windowStart;

    /**
     * Window size in seconds
     */
    private int $windowSize = 60;

    /**
     * Constructor
     */
    public function __construct(int $windowSize = 60)
    {
        $this->windowSize = $windowSize;
        $this->windowStart = time();
    }

    /**
     * Record a borrow operation
     */
    public function recordBorrow(string $type): void
    {
        $timestamp = microtime(true);

        $this->metrics['borrows'][] = [
            'type' => $type,
            'timestamp' => $timestamp,
            'window' => $this->getCurrentWindow(),
        ];

        $this->updateTimeSeries('borrows', $type);
    }

    /**
     * Record a return operation
     */
    public function recordReturn(string $type): void
    {
        $timestamp = microtime(true);

        $this->metrics['returns'][] = [
            'type' => $type,
            'timestamp' => $timestamp,
            'window' => $this->getCurrentWindow(),
        ];

        $this->updateTimeSeries('returns', $type);
    }

    /**
     * Record pool expansion
     */
    public function recordExpansion(string $type, int $oldSize, int $newSize): void
    {
        $timestamp = microtime(true);

        $this->metrics['expansions'][] = [
            'type' => $type,
            'timestamp' => $timestamp,
            'old_size' => $oldSize,
            'new_size' => $newSize,
            'growth' => $newSize - $oldSize,
            'growth_rate' => ($newSize - $oldSize) / $oldSize,
        ];
    }

    /**
     * Record pool shrink
     */
    public function recordShrink(string $type, int $oldSize, int $newSize): void
    {
        $timestamp = microtime(true);

        $this->metrics['shrinks'][] = [
            'type' => $type,
            'timestamp' => $timestamp,
            'old_size' => $oldSize,
            'new_size' => $newSize,
            'reduction' => $oldSize - $newSize,
            'reduction_rate' => ($oldSize - $newSize) / $oldSize,
        ];
    }

    /**
     * Record emergency activation
     */
    public function recordEmergencyActivation(): void
    {
        $this->metrics['emergency_activations'][] = [
            'timestamp' => microtime(true),
            'window' => $this->getCurrentWindow(),
        ];
    }

    /**
     * Record performance metric
     */
    public function recordPerformance(string $operation, float $duration): void
    {
        $this->metrics['performance'][] = [
            'operation' => $operation,
            'duration' => $duration,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get current window
     */
    private function getCurrentWindow(): int
    {
        return (int) floor(time() / $this->windowSize);
    }

    /**
     * Update time series data
     */
    private function updateTimeSeries(string $metric, string $type): void
    {
        $window = $this->getCurrentWindow();

        if (!isset($this->timeSeries[$window])) {
            $this->timeSeries[$window] = [];
        }

        if (!isset($this->timeSeries[$window][$metric])) {
            $this->timeSeries[$window][$metric] = [];
        }

        if (!isset($this->timeSeries[$window][$metric][$type])) {
            $this->timeSeries[$window][$metric][$type] = 0;
        }

        $this->timeSeries[$window][$metric][$type]++;

        // Clean old windows
        $this->cleanOldWindows();
    }

    /**
     * Clean old windows
     */
    private function cleanOldWindows(): void
    {
        $currentWindow = $this->getCurrentWindow();
        $maxAge = 10; // Keep 10 windows

        foreach ($this->timeSeries as $window => $data) {
            if ($window < $currentWindow - $maxAge) {
                unset($this->timeSeries[$window]);
            }
        }
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        return [
            'summary' => $this->getSummary(),
            'time_series' => $this->timeSeries,
            'performance' => $this->getPerformanceStats(),
            'health' => $this->getHealthIndicators(),
        ];
    }

    /**
     * Get summary statistics
     */
    private function getSummary(): array
    {
        $recentWindow = time() - $this->windowSize;

        $recentBorrows = array_filter(
            $this->metrics['borrows'],
            fn($m) => $m['timestamp'] > $recentWindow
        );

        $recentReturns = array_filter(
            $this->metrics['returns'],
            fn($m) => $m['timestamp'] > $recentWindow
        );

        return [
            'total_borrows' => count($this->metrics['borrows']),
            'total_returns' => count($this->metrics['returns']),
            'recent_borrows' => count($recentBorrows),
            'recent_returns' => count($recentReturns),
            'total_expansions' => count($this->metrics['expansions']),
            'total_shrinks' => count($this->metrics['shrinks']),
            'emergency_activations' => count($this->metrics['emergency_activations']),
            'borrow_rate' => $this->calculateRate('borrows'),
            'return_rate' => $this->calculateRate('returns'),
        ];
    }

    /**
     * Calculate rate per second
     */
    private function calculateRate(string $metric): float
    {
        $recentWindow = time() - $this->windowSize;
        $recent = array_filter(
            $this->metrics[$metric],
            fn($m) => $m['timestamp'] > $recentWindow
        );

        if (empty($recent)) {
            return 0.0;
        }

        return count($recent) / $this->windowSize;
    }

    /**
     * Get performance statistics
     */
    private function getPerformanceStats(): array
    {
        if (empty($this->metrics['performance'])) {
            return [
                'avg_duration' => 0,
                'min_duration' => 0,
                'max_duration' => 0,
                'p50' => 0,
                'p95' => 0,
                'p99' => 0,
            ];
        }

        $durations = array_column($this->metrics['performance'], 'duration');
        sort($durations);

        return [
            'avg_duration' => array_sum($durations) / count($durations),
            'min_duration' => min($durations),
            'max_duration' => max($durations),
            'p50' => $this->percentile($durations, 0.50),
            'p95' => $this->percentile($durations, 0.95),
            'p99' => $this->percentile($durations, 0.99),
        ];
    }

    /**
     * Calculate percentile
     */
    private function percentile(array $values, float $percentile): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $index = (int) ceil(count($values) * $percentile) - 1;
        return $values[$index] ?? 0.0;
    }

    /**
     * Get health indicators
     */
    private function getHealthIndicators(): array
    {
        $borrowRate = $this->calculateRate('borrows');
        $returnRate = $this->calculateRate('returns');
        $imbalance = abs($borrowRate - $returnRate) / max($borrowRate, 1);

        $recentEmergencies = array_filter(
            $this->metrics['emergency_activations'],
            fn($e) => $e['timestamp'] > time() - 300 // Last 5 minutes
        );

        return [
            'status' => $this->determineHealthStatus($imbalance, count($recentEmergencies)),
            'imbalance_ratio' => $imbalance,
            'recent_emergencies' => count($recentEmergencies),
            'expansion_rate' => count($this->metrics['expansions']) / max(1, time() - $this->windowStart),
            'recommendations' => $this->generateRecommendations($imbalance, $borrowRate),
        ];
    }

    /**
     * Determine health status
     */
    private function determineHealthStatus(float $imbalance, int $emergencies): string
    {
        if ($emergencies > 0) {
            return 'critical';
        }

        if ($imbalance > 0.5) {
            return 'warning';
        }

        if ($imbalance > 0.2) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations(float $imbalance, float $borrowRate): array
    {
        $recommendations = [];

        if ($imbalance > 0.5) {
            $recommendations[] = 'High imbalance detected - consider increasing pool size';
        }

        if ($borrowRate > 100) {
            $recommendations[] = 'High borrow rate - enable auto-scaling if not already enabled';
        }

        if (count($this->metrics['emergency_activations']) > 5) {
            $recommendations[] = 'Multiple emergency activations - increase max pool size';
        }

        return $recommendations;
    }

    /**
     * Export metrics for monitoring systems
     */
    public function export(): array
    {
        $summary = $this->getSummary();
        $performance = $this->getPerformanceStats();
        $health = $this->getHealthIndicators();

        return [
            'pool_borrows_total' => $summary['total_borrows'],
            'pool_returns_total' => $summary['total_returns'],
            'pool_borrow_rate' => $summary['borrow_rate'],
            'pool_return_rate' => $summary['return_rate'],
            'pool_expansions_total' => $summary['total_expansions'],
            'pool_shrinks_total' => $summary['total_shrinks'],
            'pool_emergency_activations' => $summary['emergency_activations'],
            'pool_avg_operation_duration' => $performance['avg_duration'],
            'pool_p99_operation_duration' => $performance['p99'],
            'pool_health_status' => $health['status'],
            'pool_imbalance_ratio' => $health['imbalance_ratio'],
        ];
    }
}
