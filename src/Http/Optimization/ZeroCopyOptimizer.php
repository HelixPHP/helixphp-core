<?php

declare(strict_types=1);

namespace Express\Http\Optimization;

/**
 * Zero-Copy Optimization Manager
 *
 * Implements zero-copy techniques to minimize memory allocations
 * and data copying operations for better performance.
 *
 * @package Express\Http\Optimization
 * @since 2.3.0
 */
class ZeroCopyOptimizer
{
    /**
     * Memory pools for different data types
     *
     * @var array<string, array>
     */
    private static array $memoryPools = [
        'strings' => [],
        'arrays' => [],
        'objects' => []
    ];

    /**
     * Reference tracking for zero-copy operations
     *
     * @var array<string, array>
     */
    private static array $references = [];

    /**
     * Optimization statistics
     *
     * @var array<string, int>
     */
    private static array $stats = [
        'copies_avoided' => 0,
        'memory_saved' => 0,
        'references_created' => 0,
        'pool_hits' => 0,
        'pool_misses' => 0
    ];

    /**
     * String interning pool for common strings
     *
     * @var array<string, string>
     */
    private static array $internedStrings = [];

    /**
     * Buffer pools for different sizes
     *
     * @var array<int, array>
     */
    private static array $bufferPools = [];

    /**
     * Maximum pool sizes to prevent memory leaks
     */
    private const MAX_POOL_SIZES = [
        'strings' => 1000,
        'arrays' => 500,
        'objects' => 200,
        'buffers' => 100
    ];

    /**
     * Intern string to avoid duplicates
     */
    public static function internString(string $str): string
    {
        if (isset(self::$internedStrings[$str])) {
            self::$stats['copies_avoided']++;
            return self::$internedStrings[$str];
        }

        // Only intern strings that are likely to be reused
        if (strlen($str) > 10 && strlen($str) < 1000) {
            self::$internedStrings[$str] = $str;

            // Prevent memory bloat
            if (count(self::$internedStrings) > self::MAX_POOL_SIZES['strings']) {
                // Remove oldest entries (FIFO)
                $keys = array_keys(self::$internedStrings);
                $toRemove = array_slice($keys, 0, 100);
                foreach ($toRemove as $key) {
                    unset(self::$internedStrings[$key]);
                }
            }
        }

        return $str;
    }

    /**
     * Create reference instead of copying array
     */
    public static function createArrayReference(array &$source, string $refId = null): string
    {
        $refId = $refId ?? uniqid('arr_ref_', true);

        self::$references[$refId] = [
            'type' => 'array',
            'data' => &$source,
            'created_at' => time(),
            'access_count' => 0
        ];

        self::$stats['references_created']++;
        return $refId;
    }

    /**
     * Get array by reference
     */
    public static function getArrayByReference(string $refId): ?array
    {
        if (isset(self::$references[$refId]) && self::$references[$refId]['type'] === 'array') {
            self::$references[$refId]['access_count']++;
            self::$stats['copies_avoided']++;
            return self::$references[$refId]['data'];
        }

        return null;
    }

    /**
     * Get reusable buffer from pool
     */
    public static function getBuffer(int $size): string
    {
        $poolKey = self::getBufferPoolKey($size);

        if (isset(self::$bufferPools[$poolKey]) && !empty(self::$bufferPools[$poolKey])) {
            $buffer = array_pop(self::$bufferPools[$poolKey]);
            self::$stats['pool_hits']++;
            return $buffer;
        }

        self::$stats['pool_misses']++;
        return str_repeat("\0", $size);
    }

    /**
     * Return buffer to pool for reuse
     */
    public static function returnBuffer(string $buffer, int $size): void
    {
        $poolKey = self::getBufferPoolKey($size);

        if (!isset(self::$bufferPools[$poolKey])) {
            self::$bufferPools[$poolKey] = [];
        }

        // Clear buffer content for security
        $buffer = str_repeat("\0", strlen($buffer));

        if (count(self::$bufferPools[$poolKey]) < self::MAX_POOL_SIZES['buffers']) {
            self::$bufferPools[$poolKey][] = $buffer;
        }
    }

    /**
     * Get buffer pool key based on size
     */
    private static function getBufferPoolKey(int $size): int
    {
        // Round up to nearest power of 2 for efficient pooling
        return 1 << (int) ceil(log($size, 2));
    }

    /**
     * Create copy-on-write wrapper for large objects
     */
    public static function createCOWWrapper(object $object, string $id = null): string
    {
        $id = $id ?? uniqid('cow_', true);

        self::$references[$id] = [
            'type' => 'cow_object',
            'original' => $object,
            'modified' => false,
            'copy' => null,
            'created_at' => time(),
            'access_count' => 0
        ];

        self::$stats['references_created']++;
        return $id;
    }

    /**
     * Get object with copy-on-write semantics
     */
    public static function getCOWObject(string $id, bool $forWrite = false): ?object
    {
        if (!isset(self::$references[$id]) || self::$references[$id]['type'] !== 'cow_object') {
            return null;
        }

        $ref = &self::$references[$id];
        $ref['access_count']++;

        if (!$forWrite) {
            // Read access - return original
            return $ref['original'];
        }

        // Write access - create copy if needed
        if (!$ref['modified']) {
            $ref['copy'] = clone $ref['original'];
            $ref['modified'] = true;
            self::$stats['copies_avoided']++; // We delayed the copy until actually needed
        }

        return $ref['copy'];
    }

    /**
     * Create view of array slice without copying
     */
    public static function createArrayView(array &$source, int $offset, int $length = null): ArrayView
    {
        return new ArrayView($source, $offset, $length);
    }

    /**
     * Stream data without loading into memory
     */
    public static function createStreamView(string $filePath, int $offset = 0, int $length = null): StreamView
    {
        return new StreamView($filePath, $offset, $length);
    }

    /**
     * Optimize string concatenation to reduce copies
     */
    public static function efficientConcat(array $strings): string
    {
        // Calculate total length first to allocate once
        $totalLength = array_sum(array_map('strlen', $strings));

        if ($totalLength === 0) {
            return '';
        }

        // Use a single buffer allocation
        $buffer = self::getBuffer($totalLength);
        $position = 0;

        foreach ($strings as $str) {
            $len = strlen($str);
            if ($len > 0) {
                // Use substr_replace for zero-copy-like behavior
                $buffer = substr_replace($buffer, $str, $position, $len);
                $position += $len;
            }
        }

        $result = substr($buffer, 0, $totalLength);
        self::returnBuffer($buffer, $totalLength);

        self::$stats['memory_saved'] += ($totalLength * (count($strings) - 1));
        return $result;
    }

    /**
     * Memory-efficient JSON encoding without intermediate copies
     * @param resource $stream
     */
    public static function streamJsonEncode(mixed $data, $stream): int
    {
        $bytesWritten = 0;

        if (is_array($data)) {
            fwrite($stream, '[');
            $bytesWritten += 1;

            $first = true;
            foreach ($data as $item) {
                if (!$first) {
                    fwrite($stream, ',');
                    $bytesWritten += 1;
                }
                $bytesWritten += self::streamJsonEncode($item, $stream);
                $first = false;
            }

            fwrite($stream, ']');
            $bytesWritten += 1;
        } elseif (is_object($data)) {
            fwrite($stream, '{');
            $bytesWritten += 1;

            $properties = get_object_vars($data);
            $first = true;
            foreach ($properties as $key => $value) {
                if (!$first) {
                    fwrite($stream, ',');
                    $bytesWritten += 1;
                }
                $jsonKey = json_encode($key);
                if ($jsonKey !== false) {
                    fwrite($stream, $jsonKey . ':');
                    $bytesWritten += strlen($jsonKey) + 1;
                }
                $bytesWritten += self::streamJsonEncode($value, $stream);
                $first = false;
            }

            fwrite($stream, '}');
            $bytesWritten += 1;
        } else {
            $json = json_encode($data);
            if ($json !== false) {
                fwrite($stream, $json);
                $bytesWritten += strlen($json);
            }
        }

        return $bytesWritten;
    }

    /**
     * Clean up expired references
     */
    public static function cleanupReferences(): array
    {
        $cleaned = 0;
        $memoryFreed = 0;
        $currentTime = time();

        foreach (self::$references as $id => $ref) {
            $age = $currentTime - $ref['created_at'];

            // Clean up old or unused references
            if ($age > 3600 || $ref['access_count'] === 0) {
                $memoryFreed += self::estimateReferenceMemory($ref);
                unset(self::$references[$id]);
                $cleaned++;
            }
        }

        // Clean up string pool
        if (count(self::$internedStrings) > self::MAX_POOL_SIZES['strings'] * 0.8) {
            $toRemove = (int) (count(self::$internedStrings) * 0.2);
            $keys = array_keys(self::$internedStrings);
            for ($i = 0; $i < $toRemove; $i++) {
                unset(self::$internedStrings[$keys[$i]]);
            }
        }

        return [
            'references_cleaned' => $cleaned,
            'memory_freed' => $memoryFreed
        ];
    }

    /**
     * Estimate memory usage of a reference
     */
    private static function estimateReferenceMemory(array $ref): int
    {
        switch ($ref['type']) {
            case 'array':
                return strlen(serialize($ref['data']));
            case 'cow_object':
                $size = strlen(serialize($ref['original']));
                if ($ref['modified'] && $ref['copy']) {
                    $size += strlen(serialize($ref['copy']));
                }
                return $size;
            default:
                return 1024; // Default estimate
        }
    }

    /**
     * Get optimization statistics
     */
    public static function getStats(): array
    {
        return [
            'copies_avoided' => self::$stats['copies_avoided'],
            'memory_saved' => self::formatBytes(self::$stats['memory_saved']),
            'references_active' => count(self::$references),
            'interned_strings' => count(self::$internedStrings),
            'buffer_pools' => array_map('count', self::$bufferPools),
            'memory_pools' => array_map('count', self::$memoryPools),
            'pool_efficiency' => self::calculatePoolEfficiency(),
            'detailed_stats' => self::$stats
        ];
    }

    /**
     * Calculate pool efficiency
     */
    private static function calculatePoolEfficiency(): float
    {
        $totalRequests = self::$stats['pool_hits'] + self::$stats['pool_misses'];
        return $totalRequests > 0 ? (self::$stats['pool_hits'] / $totalRequests) * 100 : 0;
    }

    /**
     * Format bytes for human readability
     */
    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }

    /**
     * Reset all optimizations
     */
    public static function reset(): void
    {
        self::$memoryPools = ['strings' => [], 'arrays' => [], 'objects' => []];
        self::$references = [];
        self::$internedStrings = [];
        self::$bufferPools = [];
        self::$stats = [
            'copies_avoided' => 0,
            'memory_saved' => 0,
            'references_created' => 0,
            'pool_hits' => 0,
            'pool_misses' => 0
        ];
    }
}
