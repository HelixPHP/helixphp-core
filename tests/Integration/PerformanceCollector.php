<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

/**
 * Performance data collector for integration tests
 */
class PerformanceCollector
{
    private float $startTime;
    private int $startMemory;
    private array $metrics = [];
    private bool $collecting = false;

    public function startCollection(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->collecting = true;
        $this->metrics = [];
    }

    public function stopCollection(): array
    {
        if (!$this->collecting) {
            return [];
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $this->metrics['execution_time_ms'] = ($endTime - $this->startTime) * 1000;
        $this->metrics['memory_usage_mb'] = $endMemory / 1024 / 1024;
        $this->metrics['memory_delta_mb'] = ($endMemory - $this->startMemory) / 1024 / 1024;
        $this->metrics['peak_memory_mb'] = memory_get_peak_usage(true) / 1024 / 1024;

        $this->collecting = false;

        return $this->metrics;
    }

    public function getCurrentMetrics(): array
    {
        if (!$this->collecting) {
            return [];
        }

        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);

        return [
            'elapsed_time_ms' => ($currentTime - $this->startTime) * 1000,
            'current_memory_mb' => $currentMemory / 1024 / 1024,
            'memory_delta_mb' => ($currentMemory - $this->startMemory) / 1024 / 1024,
            'peak_memory_mb' => memory_get_peak_usage(true) / 1024 / 1024,
        ];
    }

    public function recordMetric(string $name, $value): void
    {
        $this->metrics[$name] = $value;
    }
}
