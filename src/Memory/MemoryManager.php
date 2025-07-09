<?php

declare(strict_types=1);

namespace PivotPHP\Core\Memory;

use PivotPHP\Core\Http\Pool\DynamicPool;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

/**
 * Adaptive memory management system
 */
class MemoryManager
{
    /**
     * Memory management strategies
     */
    public const STRATEGY_ADAPTIVE = 'adaptive';
    public const STRATEGY_AGGRESSIVE = 'aggressive';
    public const STRATEGY_CONSERVATIVE = 'conservative';

    /**
     * Memory pressure levels
     */
    public const PRESSURE_LOW = 'low';
    public const PRESSURE_MEDIUM = 'medium';
    public const PRESSURE_HIGH = 'high';
    public const PRESSURE_CRITICAL = 'critical';

    /**
     * Configuration
     */
    private array $config = [
        'gc_strategy' => self::STRATEGY_ADAPTIVE,
        'gc_threshold' => 0.7,              // 70% memory usage
        'emergency_gc' => 0.9,              // 90% triggers emergency GC
        'check_interval' => 5,              // Check every 5 seconds
        'object_lifetime' => [
            'request' => 300,               // 5 minutes
            'response' => 300,
            'stream' => 60,                 // 1 minute
            'uri' => 600,                   // 10 minutes
        ],
        'pool_adjustments' => [
            self::PRESSURE_LOW => 1.2,      // Increase pools by 20%
            self::PRESSURE_MEDIUM => 1.0,   // Keep current size
            self::PRESSURE_HIGH => 0.7,     // Reduce pools by 30%
            self::PRESSURE_CRITICAL => 0.5, // Reduce pools by 50%
        ],
        'gc_settings' => [
            self::STRATEGY_ADAPTIVE => [
                'collection_threshold' => 10000,
                'roots_threshold' => 10000,
            ],
            self::STRATEGY_AGGRESSIVE => [
                'collection_threshold' => 1000,
                'roots_threshold' => 500,
            ],
            self::STRATEGY_CONSERVATIVE => [
                'collection_threshold' => 50000,
                'roots_threshold' => 50000,
            ],
        ],
    ];

    /**
     * Memory state
     */
    private array $state = [
        'current_pressure' => self::PRESSURE_LOW,
        'last_check' => 0,
        'last_gc' => 0,
        'gc_count' => 0,
        'emergency_mode' => false,
        'memory_history' => [],
        'gc_history' => [],
    ];

    /**
     * Tracked objects
     */
    private array $trackedObjects = [];

    /**
     * Memory metrics
     */
    private array $metrics = [
        'gc_runs' => 0,
        'gc_collected' => 0,
        'pressure_changes' => 0,
        'pool_adjustments' => 0,
        'emergency_activations' => 0,
        'memory_peaks' => [],
    ];

    /**
     * Pool reference
     */
    private ?DynamicPool $pool = null;

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        // Configure initial GC settings
        $this->configureGC($this->config['gc_strategy']);

        // Start monitoring
        $this->startMonitoring();
    }

    /**
     * Configure GC settings
     */
    private function configureGC(string $strategy): void
    {
        if (!isset($this->config['gc_settings'][$strategy])) {
            return;
        }

        $settings = $this->config['gc_settings'][$strategy];

        // Configure GC thresholds
        gc_enable();

        // Note: These are simulated settings as PHP doesn't expose direct GC configuration
        // In a real implementation, you might use extensions or compile-time options
    }

    /**
     * Start memory monitoring
     */
    private function startMonitoring(): void
    {
        // Register shutdown function to clean up
        register_shutdown_function([$this, 'shutdown']);

        // Initial memory snapshot
        $this->recordMemorySnapshot();
    }

    /**
     * Set pool reference
     */
    public function setPool(DynamicPool $pool): void
    {
        $this->pool = $pool;
    }

    /**
     * Check memory and adjust if needed
     */
    public function check(): void
    {
        $now = time();

        // Rate limit checks
        if ($now - $this->state['last_check'] < $this->config['check_interval']) {
            return;
        }

        $this->state['last_check'] = $now;

        // Record memory snapshot
        $this->recordMemorySnapshot();

        // Calculate pressure
        $pressure = $this->calculateMemoryPressure();
        $previousPressure = $this->state['current_pressure'];

        // Update pressure state
        if ($pressure !== $previousPressure) {
            $this->state['current_pressure'] = $pressure;
            $this->metrics['pressure_changes']++;
            $this->handlePressureChange($previousPressure, $pressure);
        }

        // Check if GC needed
        if ($this->shouldRunGC()) {
            $this->runGC();
        }

        // Clean tracked objects
        $this->cleanTrackedObjects();
    }

    /**
     * Record memory snapshot
     */
    private function recordMemorySnapshot(): void
    {
        $snapshot = [
            'timestamp' => microtime(true),
            'usage' => memory_get_usage(true),
            'real_usage' => memory_get_usage(false),
            'peak' => memory_get_peak_usage(true),
            'limit' => $this->getMemoryLimit(),
        ];

        $this->state['memory_history'][] = $snapshot;

        // Keep bounded history
        if (count($this->state['memory_history']) > 100) {
            array_shift($this->state['memory_history']);
        }

        // Track peaks
        if (!isset($this->metrics['memory_peaks']['hour'])) {
            $this->metrics['memory_peaks']['hour'] = $snapshot['peak'];
        } else {
            $this->metrics['memory_peaks']['hour'] = max(
                $this->metrics['memory_peaks']['hour'],
                $snapshot['peak']
            );
        }
    }

    /**
     * Calculate memory pressure
     */
    private function calculateMemoryPressure(): string
    {
        $usage = memory_get_usage(true);
        $limit = $this->getMemoryLimit();

        if ($limit <= 0) {
            return self::PRESSURE_LOW;
        }

        $ratio = $usage / $limit;

        return match (true) {
            $ratio >= $this->config['emergency_gc'] => self::PRESSURE_CRITICAL,
            $ratio >= $this->config['gc_threshold'] => self::PRESSURE_HIGH,
            $ratio >= 0.5 => self::PRESSURE_MEDIUM,
            default => self::PRESSURE_LOW,
        };
    }

    /**
     * Get memory limit in bytes
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            // No limit, use 2GB as reasonable max
            return 2 * 1024 * 1024 * 1024;
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
     * Handle pressure change
     */
    private function handlePressureChange(string $from, string $to): void
    {
        error_log(
            sprintf(
                "Memory pressure changed: %s -> %s (%.1f%% usage)",
                $from,
                $to,
                $this->getMemoryUsagePercentage()
            )
        );

        // Adjust pools based on pressure
        if ($this->pool !== null) {
            $this->adjustPools($to);
        }

        // Enter emergency mode if critical
        if ($to === self::PRESSURE_CRITICAL && !$this->state['emergency_mode']) {
            $this->enterEmergencyMode();
        } elseif ($to !== self::PRESSURE_CRITICAL && $this->state['emergency_mode']) {
            $this->exitEmergencyMode();
        }
    }

    /**
     * Should run GC?
     */
    private function shouldRunGC(): bool
    {
        $pressure = $this->state['current_pressure'];
        $timeSinceGC = time() - $this->state['last_gc'];

        return match ($pressure) {
            self::PRESSURE_CRITICAL => true, // Always GC in critical
            self::PRESSURE_HIGH => $timeSinceGC > 10, // Every 10 seconds
            self::PRESSURE_MEDIUM => $timeSinceGC > 30, // Every 30 seconds
            default => $timeSinceGC > 60, // Every minute
        };
    }

    /**
     * Run garbage collection
     */
    private function runGC(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Run GC
        $collected = gc_collect_cycles();

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        // Record GC event
        $this->state['last_gc'] = time();
        $this->state['gc_count']++;
        $this->metrics['gc_runs']++;
        $this->metrics['gc_collected'] += $collected;

        $gcEvent = [
            'timestamp' => $startTime,
            'duration' => ($endTime - $startTime) * 1000, // ms
            'collected' => $collected,
            'memory_freed' => $startMemory - $endMemory,
            'pressure' => $this->state['current_pressure'],
        ];

        $this->state['gc_history'][] = $gcEvent;

        // Keep bounded history
        if (count($this->state['gc_history']) > 50) {
            array_shift($this->state['gc_history']);
        }

        // Log significant GC events
        if ($collected > 1000 || $gcEvent['duration'] > 100) {
            error_log(
                sprintf(
                    "GC completed: collected %d objects, freed %.2fMB in %.2fms",
                    $collected,
                    $gcEvent['memory_freed'] / 1024 / 1024,
                    $gcEvent['duration']
                )
            );
        }
    }

    /**
     * Adjust pools based on pressure
     */
    private function adjustPools(string $pressure): void
    {
        if (!isset($this->config['pool_adjustments'][$pressure])) {
            return;
        }

        $factor = $this->config['pool_adjustments'][$pressure];

        if ($factor === 1.0) {
            return; // No adjustment needed
        }

        $this->metrics['pool_adjustments']++;

        // Update pool configuration
        $stats = $this->pool !== null ? $this->pool->getStats() : [];
        $currentConfig = $stats['config'];

        $newConfig = [
            'max_size' => (int) ($currentConfig['max_size'] * $factor),
            'emergency_limit' => (int) ($currentConfig['emergency_limit'] * $factor),
        ];

        // Apply new configuration
        // Note: In real implementation, pool would need updateConfig method
        error_log(
            sprintf(
                "Adjusting pool sizes by %.0f%% due to %s memory pressure",
                ($factor - 1) * 100,
                $pressure
            )
        );
    }

    /**
     * Enter emergency mode
     */
    private function enterEmergencyMode(): void
    {
        $this->state['emergency_mode'] = true;
        $this->metrics['emergency_activations']++;

        error_log(
            sprintf(
                "EMERGENCY: Entering memory emergency mode at %.1f%% usage",
                $this->getMemoryUsagePercentage()
            )
        );

        // Aggressive actions
        $this->runGC();

        // Clear caches
        $this->clearCaches();

        // Reduce pool sizes dramatically
        if ($this->pool !== null) {
            $this->adjustPools(self::PRESSURE_CRITICAL);
        }
    }

    /**
     * Exit emergency mode
     */
    private function exitEmergencyMode(): void
    {
        $this->state['emergency_mode'] = false;

        error_log(
            sprintf(
                "Memory emergency mode deactivated at %.1f%% usage",
                $this->getMemoryUsagePercentage()
            )
        );
    }

    /**
     * Clear various caches
     */
    private function clearCaches(): void
    {
        // Clear opcode cache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        // Clear realpath cache
        clearstatcache(true);

        // Notify application to clear caches
        // In real implementation, would trigger cache clear events
    }

    /**
     * Track object for lifecycle management
     */
    public function trackObject(string $type, object $object, array $metadata = []): void
    {
        $id = spl_object_id($object);

        $this->trackedObjects[$id] = [
            'type' => $type,
            'object' => \WeakReference::create($object),
            'created_at' => microtime(true),
            'metadata' => $metadata,
        ];
    }

    /**
     * Clean tracked objects
     */
    private function cleanTrackedObjects(): void
    {
        $now = microtime(true);
        $cleaned = 0;

        foreach ($this->trackedObjects as $id => $tracked) {
            // Check if object still exists
            if ($tracked['object']->get() === null) {
                unset($this->trackedObjects[$id]);
                $cleaned++;
                continue;
            }

            // Check lifetime
            $lifetime = $this->config['object_lifetime'][$tracked['type']] ?? 300;
            if ($now - $tracked['created_at'] > $lifetime) {
                unset($this->trackedObjects[$id]);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            error_log("Cleaned $cleaned expired tracked objects");
        }
    }

    /**
     * Get memory usage percentage
     */
    private function getMemoryUsagePercentage(): float
    {
        $usage = memory_get_usage(true);
        $limit = $this->getMemoryLimit();

        return $limit > 0 ? ($usage / $limit) * 100 : 0.0;
    }

    /**
     * Get memory status
     */
    public function getStatus(): array
    {
        return [
            'pressure' => $this->state['current_pressure'],
            'emergency_mode' => $this->state['emergency_mode'],
            'usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => $this->getMemoryLimit(),
                'percentage' => round($this->getMemoryUsagePercentage(), 2),
            ],
            'gc' => [
                'strategy' => $this->config['gc_strategy'],
                'runs' => $this->state['gc_count'],
                'last_run' => $this->state['last_gc'],
                'collected_total' => $this->metrics['gc_collected'],
            ],
            'tracked_objects' => count($this->trackedObjects),
        ];
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        $recentGC = array_slice($this->state['gc_history'], -10);
        $avgGCDuration = 0;
        $avgGCFreed = 0;

        if (!empty($recentGC)) {
            $durations = array_column($recentGC, 'duration');
            $freed = array_column($recentGC, 'memory_freed');

            $avgGCDuration = array_sum($durations) / count($durations);
            $avgGCFreed = array_sum($freed) / count($freed);
        }

        return array_merge(
            $this->metrics,
            [
                'current_pressure' => $this->state['current_pressure'],
                'memory_usage_percent' => round($this->getMemoryUsagePercentage(), 2),
                'avg_gc_duration_ms' => round($avgGCDuration, 2),
                'avg_gc_freed_mb' => round($avgGCFreed / 1024 / 1024, 2),
                'gc_frequency' => $this->getGCFrequency(),
                'memory_trend' => $this->getMemoryTrend(),
            ]
        );
    }

    /**
     * Get GC frequency (per minute)
     */
    private function getGCFrequency(): float
    {
        $recentGC = array_filter(
            $this->state['gc_history'],
            fn($gc) => $gc['timestamp'] > microtime(true) - 60
        );

        return count($recentGC);
    }

    /**
     * Get memory trend
     */
    private function getMemoryTrend(): string
    {
        if (count($this->state['memory_history']) < 5) {
            return 'stable';
        }

        $recent = array_slice($this->state['memory_history'], -5);
        $first = $recent[0]['usage'];
        $last = end($recent)['usage'];

        $change = ($last - $first) / $first;

        return match (true) {
            $change > 0.1 => 'increasing',
            $change < -0.1 => 'decreasing',
            default => 'stable',
        };
    }

    /**
     * Force GC (for testing/debugging)
     */
    public function forceGC(): void
    {
        $this->runGC();
    }

    /**
     * Shutdown cleanup
     */
    public function shutdown(): void
    {
        // Final GC
        gc_collect_cycles();

        // Log final metrics
        error_log(
            sprintf(
                "Memory manager shutdown - Total GC runs: %d, Total collected: %d",
                $this->metrics['gc_runs'],
                $this->metrics['gc_collected']
            )
        );
    }
}
