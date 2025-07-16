<?php

declare(strict_types=1);

namespace PivotPHP\Core\Memory;

/**
 * Memory Manager
 *
 * Simple and effective memory management for the microframework.
 * Provides basic monitoring and cleanup without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class MemoryManager
{
    /**
     * Memory thresholds (in bytes)
     */
    private int $warningThreshold;
    private int $criticalThreshold;
    private bool $autoGc = true;

    /**
     * Constructor
     */
    public function __construct(?int $warningThreshold = null, ?int $criticalThreshold = null)
    {
        $this->warningThreshold = $warningThreshold ?? (128 * 1024 * 1024); // 128MB
        $this->criticalThreshold = $criticalThreshold ?? (256 * 1024 * 1024); // 256MB
    }

    /**
     * Enable auto garbage collection
     */
    public function enableAutoGc(): void
    {
        $this->autoGc = true;
    }

    /**
     * Disable auto garbage collection
     */
    public function disableAutoGc(): void
    {
        $this->autoGc = false;
    }

    /**
     * Check current memory usage
     */
    public function checkMemoryUsage(): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        $status = 'normal';
        if ($current > $this->criticalThreshold) {
            $status = 'critical';
            if ($this->autoGc) {
                $this->performGarbageCollection();
            }
        } elseif ($current > $this->warningThreshold) {
            $status = 'warning';
        }

        return [
            'current_usage' => $current,
            'peak_usage' => $peak,
            'status' => $status,
            'warning_threshold' => $this->warningThreshold,
            'critical_threshold' => $this->criticalThreshold,
        ];
    }

    /**
     * Perform garbage collection
     */
    public function performGarbageCollection(): array
    {
        $before = memory_get_usage(true);

        // Force garbage collection
        gc_collect_cycles();

        $after = memory_get_usage(true);
        $freed = $before - $after;

        return [
            'memory_before' => $before,
            'memory_after' => $after,
            'memory_freed' => $freed,
            'cycles_collected' => gc_collect_cycles(),
        ];
    }

    /**
     * Get simple memory statistics
     */
    public function getStats(): array
    {
        $usage = $this->checkMemoryUsage();

        return [
            'memory_usage' => $usage['current_usage'],
            'memory_peak' => $usage['peak_usage'],
            'memory_status' => $usage['status'],
            'auto_gc_enabled' => $this->autoGc,
            'formatted_usage' => $this->formatBytes($usage['current_usage']),
            'formatted_peak' => $this->formatBytes($usage['peak_usage']),
        ];
    }

    /**
     * Format bytes for human readability
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
