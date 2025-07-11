<?php

declare(strict_types=1);

namespace PivotPHP\Core\Json\Pool;

/**
 * High-performance JSON buffer pool
 *
 * Manages a pool of JsonBuffer objects for optimal memory usage
 * and performance in JSON operations.
 *
 * @package PivotPHP\Core\Json\Pool
 * @since 1.1.1
 */
class JsonBufferPool
{
    // JSON Size Estimation Constants
    private const STRING_OVERHEAD = 20;              // Quotes + escaping overhead
    private const EMPTY_ARRAY_SIZE = 2;              // []
    private const SMALL_ARRAY_SIZE = 512;            // Small array estimate (< 10 items)
    private const MEDIUM_ARRAY_SIZE = 2048;          // Medium array estimate (< 100 items)
    private const LARGE_ARRAY_SIZE = 8192;           // Large array estimate (< 1000 items)
    private const XLARGE_ARRAY_SIZE = 32768;         // XLarge array estimate (>= 1000 items)

    // Array size thresholds
    private const SMALL_ARRAY_THRESHOLD = 10;        // Threshold for small array
    private const MEDIUM_ARRAY_THRESHOLD = 100;      // Threshold for medium array
    private const LARGE_ARRAY_THRESHOLD = 1000;     // Threshold for large array

    // Object size estimation constants
    private const OBJECT_PROPERTY_OVERHEAD = 50;     // Bytes per object property
    private const OBJECT_BASE_SIZE = 100;            // Base size for objects

    // Primitive type size constants
    private const BOOLEAN_OR_NULL_SIZE = 10;         // Size for boolean/null values
    private const NUMERIC_SIZE = 20;                 // Size for numeric values
    private const DEFAULT_ESTIMATE = 100;            // Default fallback estimate

    // Buffer capacity constants
    private const MIN_LARGE_BUFFER_SIZE = 65536;     // Minimum size for very large buffers (64KB)
    private const BUFFER_SIZE_MULTIPLIER = 2;        // Multiplier for buffer size calculation

    // Pooling decision thresholds (for determining when to use pooled encoding)
    public const POOLING_ARRAY_THRESHOLD = 10;       // Arrays with 10+ elements use pooling
    public const POOLING_OBJECT_THRESHOLD = 5;       // Objects with 5+ properties use pooling
    public const POOLING_STRING_THRESHOLD = 1024;    // Strings longer than 1KB use pooling

    /**
     * Buffer pools organized by capacity
     */
    private static array $pools = [];

    /**
     * Pool configuration
     */
    private static array $config = [
        'max_pool_size' => 50,
        'default_capacity' => 4096,
        'size_categories' => [
            'small' => 1024,      // 1KB
            'medium' => 4096,     // 4KB
            'large' => 16384,     // 16KB
            'xlarge' => 65536     // 64KB
        ]
    ];

    /**
     * Pool statistics
     */
    private static array $stats = [
        'allocations' => 0,
        'deallocations' => 0,
        'reuses' => 0,
        'peak_usage' => 0,
        'current_usage' => 0
    ];

    /**
     * Get a buffer from the pool or create new one
     */
    public static function getBuffer(?int $capacity = null): JsonBuffer
    {
        $capacity = $capacity ?? self::$config['default_capacity'];
        $poolKey = self::getPoolKey($capacity);

        if (!isset(self::$pools[$poolKey])) {
            self::$pools[$poolKey] = [];
        }

        // Try to reuse from pool
        if (!empty(self::$pools[$poolKey])) {
            $buffer = array_pop(self::$pools[$poolKey]);
            self::$stats['reuses']++;
            self::$stats['current_usage']++;

            // Reset buffer for reuse
            $buffer->reset();
            return $buffer;
        }

        // Create new buffer
        $buffer = new JsonBuffer($capacity);
        self::$stats['allocations']++;
        self::$stats['current_usage']++;

        // Update peak usage
        if (self::$stats['current_usage'] > self::$stats['peak_usage']) {
            self::$stats['peak_usage'] = self::$stats['current_usage'];
        }

        return $buffer;
    }

    /**
     * Return a buffer to the pool
     */
    public static function returnBuffer(JsonBuffer $buffer): void
    {
        $capacity = $buffer->getCapacity();
        $poolKey = self::getPoolKey($capacity);

        if (!isset(self::$pools[$poolKey])) {
            self::$pools[$poolKey] = [];
        }

        // Check if pool has space
        if (count(self::$pools[$poolKey]) < self::$config['max_pool_size']) {
            // Reset buffer before returning to pool
            $buffer->reset();
            self::$pools[$poolKey][] = $buffer;
            self::$stats['deallocations']++;
        }

        self::$stats['current_usage']--;
    }

    /**
     * Encode data using pooled buffer
     */
    public static function encodeWithPool(
        mixed $data,
        int $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ): string {
        $optimalCapacity = self::getOptimalCapacity($data);
        $buffer = self::getBuffer($optimalCapacity);

        try {
            $buffer->appendJson($data, $flags);
            return $buffer->finalize();
        } finally {
            self::returnBuffer($buffer);
        }
    }

    /**
     * Get pool statistics
     */
    public static function getStatistics(): array
    {
        $totalOperations = (int)self::$stats['allocations'] + (int)self::$stats['reuses'];
        $reuseRate = $totalOperations > 0 ? ((int)self::$stats['reuses'] / $totalOperations) * 100 : 0;

        // Map pool keys to readable format with capacity information
        $poolSizes = [];
        $poolsByCapacity = [];
        $totalBuffersInPools = 0;

        foreach (self::$pools as $key => $pool) {
            $poolSize = count($pool);
            $totalBuffersInPools += $poolSize;

            // Extract capacity from key (format: "buffer_{capacity}")
            if (preg_match('/^buffer_(\d+)$/', $key, $matches)) {
                $capacity = (int)$matches[1];
                $readableKey = self::formatCapacity($capacity);

                $poolSizes[$readableKey] = $poolSize;
                $poolsByCapacity[$capacity] = [
                    'key' => $key,
                    'capacity_bytes' => $capacity,
                    'capacity_formatted' => $readableKey,
                    'buffers_available' => $poolSize
                ];
            } else {
                // Fallback for unexpected key format
                $poolSizes[$key] = $poolSize;
            }
        }

        // Sort pools by capacity for better readability
        ksort($poolsByCapacity);

        // Sort pool_sizes by extracting numeric capacity for consistent ordering
        uksort(
            $poolSizes,
            function ($a, $b) {
            // Extract numeric capacity from formatted strings like "1.0KB (1024 bytes)"
                preg_match('/\((\d+) bytes\)/', $a, $matchesA);
                preg_match('/\((\d+) bytes\)/', $b, $matchesB);

                $capacityA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
                $capacityB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;

                return $capacityA <=> $capacityB;
            }
        );

        return [
            'reuse_rate' => round($reuseRate, 2),
            'total_operations' => $totalOperations,
            'current_usage' => self::$stats['current_usage'],
            'peak_usage' => self::$stats['peak_usage'],
            'total_buffers_pooled' => $totalBuffersInPools,
            'active_pool_count' => count(array_filter(self::$pools, fn($p) => count($p) > 0)),
            'pool_sizes' => $poolSizes,  // Legacy format sorted by capacity
            'pools_by_capacity' => array_values($poolsByCapacity),  // Enhanced format
            'detailed_stats' => self::$stats
        ];
    }

    /**
     * Format capacity in human-readable form
     */
    private static function formatCapacity(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return sprintf('%.1fMB (%d bytes)', $bytes / (1024 * 1024), $bytes);
        } elseif ($bytes >= 1024) {
            return sprintf('%.1fKB (%d bytes)', $bytes / 1024, $bytes);
        } else {
            return sprintf('%d bytes', $bytes);
        }
    }

    /**
     * Clear all pools (useful for testing)
     */
    public static function clearPools(): void
    {
        self::$pools = [];
        self::$stats = [
            'allocations' => 0,
            'deallocations' => 0,
            'reuses' => 0,
            'peak_usage' => 0,
            'current_usage' => 0
        ];
    }

    /**
     * Reset configuration to defaults (useful for testing)
     */
    public static function resetConfiguration(): void
    {
        self::$config = [
            'max_pool_size' => 50,
            'default_capacity' => 4096,
            'size_categories' => [
                'small' => 1024,      // 1KB
                'medium' => 4096,     // 4KB
                'large' => 16384,     // 16KB
                'xlarge' => 65536     // 64KB
            ]
        ];
    }

    /**
     * Configure pool settings
     */
    public static function configure(array $config): void
    {
        self::validateConfiguration($config);
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Validate configuration parameters
     */
    private static function validateConfiguration(array $config): void
    {
        // Validate 'max_pool_size'
        if (isset($config['max_pool_size'])) {
            if (!is_int($config['max_pool_size'])) {
                throw new \InvalidArgumentException(
                    "'max_pool_size' must be an integer, got: " . gettype($config['max_pool_size'])
                );
            }
            if ($config['max_pool_size'] <= 0) {
                throw new \InvalidArgumentException("'max_pool_size' must be a positive integer");
            }
            if ($config['max_pool_size'] > 1000) {
                throw new \InvalidArgumentException(
                    "'max_pool_size' cannot exceed 1000 for memory safety, got: {$config['max_pool_size']}"
                );
            }
        }

        // Validate 'default_capacity'
        if (isset($config['default_capacity'])) {
            if (!is_int($config['default_capacity'])) {
                throw new \InvalidArgumentException(
                    "'default_capacity' must be an integer, got: " . gettype($config['default_capacity'])
                );
            }
            if ($config['default_capacity'] <= 0) {
                throw new \InvalidArgumentException("'default_capacity' must be a positive integer");
            }
            if ($config['default_capacity'] > 1024 * 1024) { // 1MB limit
                throw new \InvalidArgumentException(
                    "'default_capacity' cannot exceed 1MB (1048576 bytes), got: {$config['default_capacity']}"
                );
            }
        }

        // Validate 'size_categories'
        if (isset($config['size_categories'])) {
            if (!is_array($config['size_categories'])) {
                throw new \InvalidArgumentException(
                    "'size_categories' must be an array, got: " . gettype($config['size_categories'])
                );
            }

            if (empty($config['size_categories'])) {
                throw new \InvalidArgumentException("'size_categories' cannot be empty");
            }

            foreach ($config['size_categories'] as $name => $capacity) {
                if (!is_string($name) || empty($name)) {
                    throw new \InvalidArgumentException("Size category names must be non-empty strings");
                }

                if (!is_int($capacity)) {
                    throw new \InvalidArgumentException(
                        "Size category '{$name}' must have an integer capacity, got: " . gettype($capacity)
                    );
                }
                if ($capacity <= 0) {
                    throw new \InvalidArgumentException(
                        "Size category '{$name}' must have a positive integer capacity"
                    );
                }

                if ($capacity > 1024 * 1024) { // 1MB limit per category
                    throw new \InvalidArgumentException(
                        "Size category '{$name}' capacity cannot exceed 1MB (1048576 bytes), got: {$capacity}"
                    );
                }
            }

            // Validate categories are in ascending order for optimal selection
            $capacities = array_values($config['size_categories']);
            $sortedCapacities = $capacities;
            sort($sortedCapacities);

            if ($capacities !== $sortedCapacities) {
                throw new \InvalidArgumentException(
                    "'size_categories' should be ordered from smallest to largest capacity for optimal selection"
                );
            }
        }

        // Check for unknown configuration keys
        $validKeys = ['max_pool_size', 'default_capacity', 'size_categories'];
        $unknownKeys = array_diff(array_keys($config), $validKeys);

        if (!empty($unknownKeys)) {
            throw new \InvalidArgumentException("Unknown configuration keys: " . implode(', ', $unknownKeys));
        }
    }

    /**
     * Get pool key for given capacity
     */
    private static function getPoolKey(int $capacity): string
    {
        // Normalize to power of 2 for efficient pooling
        $normalizedCapacity = 1;
        while ($normalizedCapacity < $capacity) {
            $normalizedCapacity <<= 1;
        }

        return "buffer_{$normalizedCapacity}";
    }

    /**
     * Estimate JSON size for data
     */
    private static function estimateJsonSize(mixed $data): int
    {
        if (is_string($data)) {
            return strlen($data) + self::STRING_OVERHEAD;
        }

        if (is_array($data)) {
            $count = count($data);
            if ($count === 0) {
                return self::EMPTY_ARRAY_SIZE;
            }

            // Estimate based on array size
            if ($count < self::SMALL_ARRAY_THRESHOLD) {
                return self::SMALL_ARRAY_SIZE;
            } elseif ($count < self::MEDIUM_ARRAY_THRESHOLD) {
                return self::MEDIUM_ARRAY_SIZE;
            } elseif ($count < self::LARGE_ARRAY_THRESHOLD) {
                return self::LARGE_ARRAY_SIZE;
            } else {
                return self::XLARGE_ARRAY_SIZE;
            }
        }

        if (is_object($data)) {
            $vars = get_object_vars($data);
            return $vars
                ? count($vars) * self::OBJECT_PROPERTY_OVERHEAD + self::OBJECT_BASE_SIZE
                : self::OBJECT_BASE_SIZE;
        }

        if (is_bool($data) || is_null($data)) {
            return self::BOOLEAN_OR_NULL_SIZE;
        }

        if (is_numeric($data)) {
            return self::NUMERIC_SIZE;
        }

        return self::DEFAULT_ESTIMATE;
    }

    /**
     * Get optimal buffer capacity for data
     */
    public static function getOptimalCapacity(mixed $data): int
    {
        $estimatedSize = self::estimateJsonSize($data);

        // Find the smallest size category that fits
        foreach (self::$config['size_categories'] as $name => $capacity) {
            if ($estimatedSize <= $capacity) {
                return $capacity;
            }
        }

        // For very large data, calculate based on estimate
        return max($estimatedSize * self::BUFFER_SIZE_MULTIPLIER, self::MIN_LARGE_BUFFER_SIZE);
    }
}
