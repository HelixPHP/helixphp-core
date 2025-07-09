<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Pool\Strategies;

use PivotPHP\Core\Http\Pool\Psr7Pool;

/**
 * Graceful fallback strategy for pool overflow
 * Creates new objects without pooling as last resort
 */
class GracefulFallback implements OverflowStrategy
{
    /**
     * Configuration
     */
    private array $config;

    /**
     * Metrics
     */
    private array $metrics = [
        'fallback_creates' => 0,
        'fallback_by_type' => [],
        'creation_times' => [],
    ];

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Graceful fallback can always handle requests
     */
    public function canHandle(string $type, array $context): bool
    {
        return true;
    }

    /**
     * Handle by creating new object without pooling
     */
    public function handle(string $type, array $params): mixed
    {
        $startTime = microtime(true);

        // Create new object
        $object = $this->createFallbackObject($type, $params);

        $creationTime = microtime(true) - $startTime;

        // Update metrics
        $this->metrics['fallback_creates']++;
        $this->metrics['fallback_by_type'][$type] =
            ($this->metrics['fallback_by_type'][$type] ?? 0) + 1;
        $this->metrics['creation_times'][] = $creationTime;

        // Keep only last 1000 creation times
        if (count($this->metrics['creation_times']) > 1000) {
            array_shift($this->metrics['creation_times']);
        }

        // Log warning if creation is slow
        if ($creationTime > 0.001) { // 1ms threshold
            $this->logSlowCreation($type, $creationTime);
        }

        return $object;
    }

    /**
     * Create fallback object
     */
    private function createFallbackObject(string $type, array $params): mixed
    {
        return match ($type) {
            'request' => $this->createFallbackRequest($params),
            'response' => $this->createFallbackResponse($params),
            'uri' => $this->createFallbackUri($params),
            'stream' => $this->createFallbackStream($params),
            default => throw new \InvalidArgumentException("Unknown type: $type"),
        };
    }

    /**
     * Create fallback request
     */
    private function createFallbackRequest(array $params): mixed
    {
        // Use PSR-7 factory directly without pooling
        $uri = Psr7Pool::getUri($params[1] ?? '/');
        $body = Psr7Pool::getStream('');

        return Psr7Pool::getServerRequest(
            $params[0] ?? 'GET',
            $uri,
            $body,
            $params[2] ?? [],
            $params[4] ?? '1.1',
            $params[5] ?? []
        );
    }

    /**
     * Create fallback response
     */
    private function createFallbackResponse(array $params): mixed
    {
        return Psr7Pool::borrowResponse(
            $params[0] ?? 200,
            $params[1] ?? [],
            $params[2] ?? null,
            $params[3] ?? '1.1',
            $params[4] ?? null
        );
    }

    /**
     * Create fallback URI
     */
    private function createFallbackUri(array $params): mixed
    {
        return Psr7Pool::borrowUri();
    }

    /**
     * Create fallback stream
     */
    private function createFallbackStream(array $params): mixed
    {
        return Psr7Pool::borrowStream();
    }

    /**
     * Log slow creation
     */
    private function logSlowCreation(string $type, float $time): void
    {
        // In real implementation, this would use a proper logger
        error_log(
            sprintf(
                "Slow fallback creation for %s: %.3fms",
                $type,
                $time * 1000
            )
        );
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        $avgCreationTime = !empty($this->metrics['creation_times'])
            ? array_sum($this->metrics['creation_times']) / count($this->metrics['creation_times'])
            : 0;

        $maxCreationTime = !empty($this->metrics['creation_times'])
            ? max($this->metrics['creation_times'])
            : 0;

        return array_merge(
            $this->metrics,
            [
                'avg_creation_time' => $avgCreationTime,
                'max_creation_time' => $maxCreationTime,
                'fallback_rate' => $this->calculateFallbackRate(),
            ]
        );
    }

    /**
     * Calculate fallback rate
     */
    private function calculateFallbackRate(): float
    {
        // This would need access to total requests to calculate properly
        // For now, return count per minute based on first/last creation
        if (count($this->metrics['creation_times']) < 2) {
            return 0.0;
        }

        // Estimate based on creation density
        $recentCreations = array_filter(
            $this->metrics['creation_times'],
            fn($time) => $time > microtime(true) - 60
        );

        return count($recentCreations) / 60.0;
    }

    /**
     * Get fallback impact assessment
     */
    public function getImpactAssessment(): array
    {
        $totalFallbacks = $this->metrics['fallback_creates'];

        return [
            'total_fallbacks' => $totalFallbacks,
            'memory_impact' => $this->estimateMemoryImpact(),
            'gc_impact' => $this->estimateGCImpact(),
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    /**
     * Estimate memory impact
     */
    private function estimateMemoryImpact(): array
    {
        $avgObjectSize = [
            'request' => 8192,  // 8KB estimated
            'response' => 4096, // 4KB estimated
            'uri' => 1024,      // 1KB estimated
            'stream' => 2048,   // 2KB estimated
        ];

        $totalMemory = 0;
        foreach ($this->metrics['fallback_by_type'] as $type => $count) {
            $totalMemory += ($avgObjectSize[$type] ?? 4096) * $count;
        }

        return [
            'estimated_bytes' => $totalMemory,
            'estimated_mb' => round($totalMemory / 1024 / 1024, 2),
        ];
    }

    /**
     * Estimate GC impact
     */
    private function estimateGCImpact(): string
    {
        $total = $this->metrics['fallback_creates'];

        return match (true) {
            $total > 10000 => 'severe',
            $total > 5000 => 'high',
            $total > 1000 => 'moderate',
            $total > 100 => 'low',
            default => 'minimal',
        };
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];
        $total = $this->metrics['fallback_creates'];

        if ($total > 1000) {
            $recommendations[] = 'High fallback usage detected - increase pool size';
        }

        if ($total > 5000) {
            $recommendations[] = 'Critical fallback levels - enable emergency mode';
        }

        $avgTime = !empty($this->metrics['creation_times'])
            ? array_sum($this->metrics['creation_times']) / count($this->metrics['creation_times'])
            : 0;

        if ($avgTime > 0.005) { // 5ms
            $recommendations[] = 'Slow object creation detected - consider pre-warming pools';
        }

        return $recommendations;
    }
}
