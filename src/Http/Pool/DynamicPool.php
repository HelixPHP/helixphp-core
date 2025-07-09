<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Pool;

use PivotPHP\Core\Http\Pool\Strategies\OverflowStrategy;
use PivotPHP\Core\Http\Pool\Strategies\ElasticExpansion;
use PivotPHP\Core\Http\Pool\Strategies\PriorityQueuing;
use PivotPHP\Core\Http\Pool\Strategies\GracefulFallback;
use PivotPHP\Core\Http\Pool\Strategies\SmartRecycling;

/**
 * Dynamic object pool with auto-scaling capabilities
 */
class DynamicPool
{
    /**
     * Pool configuration
     */
    private array $config = [
        'initial_size' => 50,
        'max_size' => 500,
        'emergency_limit' => 1000,
        'auto_scale' => true,
        'scale_threshold' => 0.8,
        'scale_factor' => 1.5,
        'cooldown_period' => 60,
        'shrink_threshold' => 0.2,
        'shrink_factor' => 0.7,
        'min_size' => 10,
    ];

    /**
     * Object pools by type
     */
    private array $pools = [];

    /**
     * Pool statistics
     */
    private array $stats = [
        'created' => 0,
        'borrowed' => 0,
        'returned' => 0,
        'expanded' => 0,
        'shrunk' => 0,
        'overflow_created' => 0,
        'emergency_activations' => 0,
    ];

    /**
     * Pool metrics tracker
     */
    private ?PoolMetrics $metrics = null;

    /**
     * Scaling state
     */
    private array $scalingState = [
        'last_expansion' => 0,
        'last_shrink' => 0,
        'current_size' => 0,
        'peak_usage' => 0,
        'in_emergency' => false,
    ];

    /**
     * Overflow strategies
     */
    private array $overflowStrategies = [];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->initializeStrategies();
        $this->metrics = new PoolMetrics();

        // Initialize pools with initial size
        $this->warmUp();
    }

    /**
     * Initialize overflow strategies
     */
    private function initializeStrategies(): void
    {
        $this->overflowStrategies = [
            'elastic' => new ElasticExpansion($this->config),
            'priority' => new PriorityQueuing($this->config),
            'fallback' => new GracefulFallback($this->config),
            'recycling' => new SmartRecycling($this->config),
        ];
    }

    /**
     * Warm up pools with initial objects
     */
    private function warmUp(): void
    {
        $types = ['request', 'response', 'uri', 'stream'];

        foreach ($types as $type) {
            $this->pools[$type] = [];
            $this->scalingState[$type] = [
                'current_size' => 0,
                'target_size' => $this->config['initial_size'],
                'peak_usage' => 0,
                'last_scale_time' => 0,
            ];

            // Create initial objects
            for ($i = 0; $i < $this->config['initial_size']; $i++) {
                $this->pools[$type][] = $this->createObject($type);
                $this->scalingState[$type]['current_size']++;
            }
        }

        $this->stats['created'] += $this->config['initial_size'] * count($types);
    }

    /**
     * Borrow an object from the pool
     */
    public function borrow(string $type, array $params = []): mixed
    {
        $this->stats['borrowed']++;
        $this->metrics?->recordBorrow($type);

        // Check if auto-scaling needed
        if ($this->config['auto_scale']) {
            $this->checkAndScale($type);
        }

        // Try to get from pool
        if (!empty($this->pools[$type])) {
            $object = array_pop($this->pools[$type]);
            $this->updateUsageMetrics($type);
            return $this->resetObject($type, $object, $params);
        }

        // Pool exhausted - use overflow strategy
        return $this->handleOverflow($type, $params);
    }

    /**
     * Return an object to the pool
     */
    public function return(string $type, mixed $object): void
    {
        $this->stats['returned']++;
        $this->metrics?->recordReturn($type);

        $currentSize = $this->scalingState[$type]['current_size'];
        $maxSize = $this->getEffectiveMaxSize($type);

        // Check if we should accept the object
        if (count($this->pools[$type]) < $maxSize) {
            $this->pools[$type][] = $this->cleanObject($type, $object);
        } else {
            // Pool is full, destroy the object
            $this->destroyObject($type, $object);
        }

        // Check if we should shrink the pool
        if ($this->config['auto_scale']) {
            $this->checkAndShrink($type);
        }
    }

    /**
     * Check and perform auto-scaling if needed
     */
    private function checkAndScale(string $type): void
    {
        $usage = $this->getPoolUsage($type);
        $currentTime = time();
        $lastScaleTime = $this->scalingState[$type]['last_scale_time'];

        // Check cooldown period
        if ($currentTime - $lastScaleTime < $this->config['cooldown_period']) {
            return;
        }

        // Check if scaling needed
        if ($usage >= $this->config['scale_threshold']) {
            $this->expandPool($type);
        }
    }

    /**
     * Expand the pool
     */
    private function expandPool(string $type): void
    {
        $currentSize = $this->scalingState[$type]['current_size'];
        $maxSize = $this->config['max_size'];

        if ($currentSize >= $maxSize) {
            // Already at max size, check emergency mode
            if (!$this->scalingState['in_emergency'] && $currentSize < $this->config['emergency_limit']) {
                $this->activateEmergencyMode();
            }
            return;
        }

        // Calculate new size
        $newSize = min(
            (int) ceil($currentSize * $this->config['scale_factor']),
            $maxSize
        );

        // Add new objects
        $toAdd = $newSize - $currentSize;
        for ($i = 0; $i < $toAdd; $i++) {
            $this->pools[$type][] = $this->createObject($type);
        }

        // Update state
        $this->scalingState[$type]['current_size'] = $newSize;
        $this->scalingState[$type]['last_scale_time'] = time();
        $this->stats['expanded']++;

        $this->metrics?->recordExpansion($type, $currentSize, $newSize);
    }

    /**
     * Check and shrink pool if underutilized
     */
    private function checkAndShrink(string $type): void
    {
        $usage = $this->getPoolUsage($type);
        $currentTime = time();
        $lastScaleTime = $this->scalingState[$type]['last_scale_time'];

        // Check cooldown period
        if ($currentTime - $lastScaleTime < $this->config['cooldown_period']) {
            return;
        }

        // Check if shrinking needed
        if ($usage <= $this->config['shrink_threshold']) {
            $this->shrinkPool($type);
        }
    }

    /**
     * Shrink the pool
     */
    private function shrinkPool(string $type): void
    {
        $currentSize = $this->scalingState[$type]['current_size'];
        $minSize = $this->config['min_size'];

        if ($currentSize <= $minSize) {
            return;
        }

        // Calculate new size
        $newSize = max(
            (int) floor($currentSize * $this->config['shrink_factor']),
            $minSize
        );

        // Remove excess objects
        $toRemove = $currentSize - $newSize;
        for ($i = 0; $i < $toRemove && !empty($this->pools[$type]); $i++) {
            $object = array_pop($this->pools[$type]);
            $this->destroyObject($type, $object);
        }

        // Update state
        $this->scalingState[$type]['current_size'] = $newSize;
        $this->scalingState[$type]['last_scale_time'] = time();
        $this->stats['shrunk']++;

        $this->metrics?->recordShrink($type, $currentSize, $newSize);
    }

    /**
     * Handle pool overflow
     */
    private function handleOverflow(string $type, array $params): mixed
    {
        $this->stats['overflow_created']++;

        // Try elastic expansion first
        if ($this->overflowStrategies['elastic']->canHandle($type, $this->scalingState)) {
            return $this->overflowStrategies['elastic']->handle($type, $params);
        }

        // Try priority queuing
        if ($this->overflowStrategies['priority']->canHandle($type, $params)) {
            return $this->overflowStrategies['priority']->handle($type, $params);
        }

        // Use graceful fallback
        return $this->overflowStrategies['fallback']->handle($type, $params);
    }

    /**
     * Activate emergency mode
     */
    private function activateEmergencyMode(): void
    {
        $this->scalingState['in_emergency'] = true;
        $this->stats['emergency_activations']++;
        $this->metrics?->recordEmergencyActivation();

        // Adjust all pool limits temporarily
        foreach ($this->scalingState as $type => &$state) {
            if (is_array($state) && isset($state['current_size'])) {
                $state['emergency_limit'] = $this->config['emergency_limit'];
            }
        }
    }

    /**
     * Get pool usage percentage
     */
    private function getPoolUsage(string $type): float
    {
        $available = count($this->pools[$type]);
        $total = $this->scalingState[$type]['current_size'];

        if ($total === 0) {
            return 0.0;
        }

        return 1.0 - ($available / $total);
    }

    /**
     * Get effective max size considering emergency mode
     */
    private function getEffectiveMaxSize(string $type): int
    {
        if ($this->scalingState['in_emergency']) {
            return $this->config['emergency_limit'];
        }

        return $this->config['max_size'];
    }

    /**
     * Update usage metrics
     */
    private function updateUsageMetrics(string $type): void
    {
        $usage = $this->getPoolUsage($type);

        if ($usage > $this->scalingState[$type]['peak_usage']) {
            $this->scalingState[$type]['peak_usage'] = $usage;
        }
    }

    /**
     * Create a new object
     */
    private function createObject(string $type): mixed
    {
        return match ($type) {
            'request' => Psr7Pool::borrowRequest(),
            'response' => Psr7Pool::borrowResponse(),
            'uri' => Psr7Pool::borrowUri(),
            'stream' => Psr7Pool::borrowStream(),
            default => throw new \InvalidArgumentException("Unknown pool type: $type"),
        };
    }

    /**
     * Reset object for reuse
     */
    private function resetObject(string $type, mixed $object, array $params): mixed
    {
        // For now, just return the object as-is since reset methods don't exist
        // In a real implementation, we would reset the object state
        return $object;
    }

    /**
     * Clean object before returning to pool
     */
    private function cleanObject(string $type, mixed $object): mixed
    {
        // Perform type-specific cleaning
        return match ($type) {
            'request' => $this->cleanRequest($object),
            'response' => $this->cleanResponse($object),
            'uri' => $object, // URIs are immutable
            'stream' => $this->cleanStream($object),
            default => $object,
        };
    }

    /**
     * Clean request object
     */
    private function cleanRequest(mixed $request): mixed
    {
        // Reset to clean state
        return $request;
    }

    /**
     * Clean response object
     */
    private function cleanResponse(mixed $response): mixed
    {
        // Reset to clean state
        return $response;
    }

    /**
     * Clean stream object
     */
    private function cleanStream(mixed $stream): mixed
    {
        // Rewind stream if possible
        if (method_exists($stream, 'rewind')) {
            $stream->rewind();
        }
        return $stream;
    }

    /**
     * Destroy object
     */
    private function destroyObject(string $type, mixed $object): void
    {
        // Type-specific cleanup if needed
        if ($type === 'stream' && method_exists($object, 'close')) {
            $object->close();
        }
    }

    /**
     * Get pool statistics
     */
    public function getStats(): array
    {
        $poolSizes = [];
        $poolUsage = [];

        foreach ($this->pools as $type => $pool) {
            $poolSizes[$type] = count($pool);
            $poolUsage[$type] = $this->getPoolUsage($type);
        }

        return [
            'stats' => $this->stats,
            'scaling_state' => $this->scalingState,
            'pool_sizes' => $poolSizes,
            'pool_usage' => $poolUsage,
            'metrics' => $this->metrics?->getMetrics() ?? [],
            'config' => $this->config,
        ];
    }

    /**
     * Reset pool to initial state
     */
    public function reset(): void
    {
        // Clear all pools
        foreach ($this->pools as $type => $pool) {
            foreach ($pool as $object) {
                $this->destroyObject($type, $object);
            }
        }

        // Reset state
        $this->pools = [];
        $this->scalingState = [];
        $this->stats = [
            'created' => 0,
            'borrowed' => 0,
            'returned' => 0,
            'expanded' => 0,
            'shrunk' => 0,
            'overflow_created' => 0,
            'emergency_activations' => 0,
        ];

        // Warm up again
        $this->warmUp();
    }
}
