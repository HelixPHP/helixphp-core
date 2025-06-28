<?php

declare(strict_types=1);

namespace Express\Http\Optimization;

/**
 * Memory Mapping Manager for Large Files
 *
 * Provides memory-mapped file I/O for efficient handling of large files
 * without loading entire contents into memory.
 *
 * @package Express\Http\Optimization
 * @since 2.3.0
 */
class MemoryMappingManager
{
    /**
     * Active memory mappings
     *
     * @var array<string, array>
     */
    private static array $mappings = [];

    /**
     * Memory mapping statistics
     *
     * @var array<string, int>
     */
    private static array $stats = [
        'mappings_created' => 0,
        'mappings_accessed' => 0,
        'bytes_mapped' => 0,
        'memory_saved' => 0,
        'cache_hits' => 0
    ];

    /**
     * Configuration for memory mapping
     *
     * @var array<string, mixed>
     */
    private static array $config = [
        'min_file_size' => 1024 * 1024,      // 1MB minimum for mapping
        'max_mapped_files' => 50,             // Maximum concurrent mappings
        'chunk_size' => 64 * 1024,           // 64KB chunks
        'cache_timeout' => 3600,              // 1 hour cache timeout
        'enable_prefetch' => true             // Enable prefetching
    ];

    /**
     * Create memory mapping for a file
     */
    public static function createMapping(string $filePath, int $offset = 0, int $length = null): ?MemoryMapping
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $fileSize = filesize($filePath);
        if ($fileSize < self::$config['min_file_size']) {
            return null; // File too small for mapping benefits
        }

        $mappingId = md5($filePath . ':' . $offset . ':' . ($length ?? $fileSize));

        // Check if mapping already exists
        if (isset(self::$mappings[$mappingId])) {
            self::$stats['cache_hits']++;
            self::$mappings[$mappingId]['last_accessed'] = time();
            return self::$mappings[$mappingId]['mapping'];
        }

        // Check if we need to clean up old mappings
        if (count(self::$mappings) >= self::$config['max_mapped_files']) {
            self::cleanupOldMappings();
        }

        $length = $length ?? ($fileSize - $offset);
        $mapping = new MemoryMapping($filePath, $offset, $length);

        self::$mappings[$mappingId] = [
            'mapping' => $mapping,
            'created_at' => time(),
            'last_accessed' => time(),
            'file_path' => $filePath,
            'size' => $length
        ];

        self::$stats['mappings_created']++;
        self::$stats['bytes_mapped'] += $length;

        return $mapping;
    }

    /**
     * Get cached mapping if available
     */
    public static function getMapping(string $filePath, int $offset = 0, int $length = null): ?MemoryMapping
    {
        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
        $mappingId = md5($filePath . ':' . $offset . ':' . ($length ?? $fileSize));

        if (isset(self::$mappings[$mappingId])) {
            self::$stats['cache_hits']++;
            self::$mappings[$mappingId]['last_accessed'] = time();
            return self::$mappings[$mappingId]['mapping'];
        }

        return null;
    }

    /**
     * Stream large file efficiently using memory mapping
     */
    public static function streamFile(string $filePath, $output, int $chunkSize = null): int
    {
        $chunkSize = $chunkSize ?? self::$config['chunk_size'];
        $mapping = self::createMapping($filePath);

        if (!$mapping) {
            // Fallback to regular file streaming
            return self::fallbackStreamFile($filePath, $output, $chunkSize);
        }

        $totalBytes = 0;
        $fileSize = $mapping->getSize();
        $position = 0;

        while ($position < $fileSize) {
            $currentChunkSize = min($chunkSize, $fileSize - $position);
            $chunk = $mapping->read($position, $currentChunkSize);

            if ($chunk === false || $chunk === '') {
                break;
            }

            $bytesWritten = fwrite($output, $chunk);
            $totalBytes += $bytesWritten;
            $position += strlen($chunk);

            // Prefetch next chunk if enabled
            if (self::$config['enable_prefetch'] && ($position + $chunkSize) < $fileSize) {
                $mapping->prefetch($position, min($chunkSize, $fileSize - $position));
            }
        }

        self::$stats['mappings_accessed']++;
        return $totalBytes;
    }

    /**
     * Fallback streaming for files that can't be memory mapped
     */
    private static function fallbackStreamFile(string $filePath, $output, int $chunkSize): int
    {
        $totalBytes = 0;
        $handle = fopen($filePath, 'rb');

        if (!$handle) {
            return 0;
        }

        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            if ($chunk === false || $chunk === '') {
                break;
            }

            $bytesWritten = fwrite($output, $chunk);
            $totalBytes += $bytesWritten;
        }

        fclose($handle);
        return $totalBytes;
    }

    /**
     * Read file section without loading entire file
     */
    public static function readFileSection(string $filePath, int $offset, int $length): ?string
    {
        $mapping = self::createMapping($filePath, $offset, $length);

        if (!$mapping) {
            // Fallback to regular file reading
            $handle = fopen($filePath, 'rb');
            if (!$handle) {
                return null;
            }

            fseek($handle, $offset);
            $data = fread($handle, $length);
            fclose($handle);

            return $data !== false ? $data : null;
        }

        return $mapping->read(0, $length);
    }

    /**
     * Search in large file without loading into memory
     */
    public static function searchInFile(string $filePath, string $needle, int $chunkSize = null): array
    {
        $chunkSize = $chunkSize ?? self::$config['chunk_size'];
        $mapping = self::createMapping($filePath);
        $matches = [];

        if (!$mapping) {
            return self::fallbackSearchInFile($filePath, $needle, $chunkSize);
        }

        $fileSize = $mapping->getSize();
        $position = 0;
        $overlap = strlen($needle) - 1;

        while ($position < $fileSize) {
            $currentChunkSize = min($chunkSize, $fileSize - $position);
            $chunk = $mapping->read($position, $currentChunkSize);

            if ($chunk === false) {
                break;
            }

            // Find all occurrences in this chunk
            $offset = 0;
            while (($pos = strpos($chunk, $needle, $offset)) !== false) {
                $matches[] = $position + $pos;
                $offset = $pos + 1;
            }

            $position += $currentChunkSize - $overlap;
        }

        return $matches;
    }

    /**
     * Fallback search for files that can't be memory mapped
     */
    private static function fallbackSearchInFile(string $filePath, string $needle, int $chunkSize): array
    {
        $matches = [];
        $handle = fopen($filePath, 'rb');

        if (!$handle) {
            return $matches;
        }

        $position = 0;
        $overlap = strlen($needle) - 1;
        $buffer = '';

        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            if ($chunk === false) {
                break;
            }

            $searchText = $buffer . $chunk;
            $offset = 0;

            while (($pos = strpos($searchText, $needle, $offset)) !== false) {
                $matches[] = $position - strlen($buffer) + $pos;
                $offset = $pos + 1;
            }

            // Keep overlap for next iteration
            $buffer = substr($searchText, -$overlap);
            $position += strlen($chunk);
        }

        fclose($handle);
        return $matches;
    }

    /**
     * Process large file line by line without loading into memory
     */
    public static function processFileLines(string $filePath, callable $processor, int $bufferSize = null): int
    {
        $bufferSize = $bufferSize ?? self::$config['chunk_size'];
        $mapping = self::createMapping($filePath);
        $linesProcessed = 0;

        if (!$mapping) {
            return self::fallbackProcessLines($filePath, $processor);
        }

        $fileSize = $mapping->getSize();
        $position = 0;
        $buffer = '';

        while ($position < $fileSize) {
            $chunkSize = min($bufferSize, $fileSize - $position);
            $chunk = $mapping->read($position, $chunkSize);

            if ($chunk === false) {
                break;
            }

            $buffer .= $chunk;
            $lines = explode("\n", $buffer);

            // Process all complete lines
            for ($i = 0; $i < count($lines) - 1; $i++) {
                $processor($lines[$i], $linesProcessed);
                $linesProcessed++;
            }

            // Keep the last incomplete line in buffer
            $buffer = $lines[count($lines) - 1];
            $position += $chunkSize;
        }

        // Process the last line if it exists
        if (!empty($buffer)) {
            $processor($buffer, $linesProcessed);
            $linesProcessed++;
        }

        return $linesProcessed;
    }

    /**
     * Fallback line processing
     */
    private static function fallbackProcessLines(string $filePath, callable $processor): int
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return 0;
        }

        $linesProcessed = 0;
        while (($line = fgets($handle)) !== false) {
            $processor(rtrim($line, "\r\n"), $linesProcessed);
            $linesProcessed++;
        }

        fclose($handle);
        return $linesProcessed;
    }

    /**
     * Clean up old mappings to free memory
     */
    private static function cleanupOldMappings(): void
    {
        $currentTime = time();
        $cleaned = 0;

        foreach (self::$mappings as $id => $mapping) {
            $age = $currentTime - $mapping['last_accessed'];

            if ($age > self::$config['cache_timeout']) {
                unset(self::$mappings[$id]);
                $cleaned++;
            }
        }

        // If still too many mappings, remove oldest
        if (count(self::$mappings) >= self::$config['max_mapped_files']) {
            uasort(self::$mappings, function($a, $b) {
                return $a['last_accessed'] <=> $b['last_accessed'];
            });

            $toRemove = count(self::$mappings) - self::$config['max_mapped_files'] + 10;
            $removed = 0;

            foreach (self::$mappings as $id => $mapping) {
                if ($removed >= $toRemove) {
                    break;
                }
                unset(self::$mappings[$id]);
                $removed++;
            }
        }
    }

    /**
     * Get memory mapping statistics
     */
    public static function getStats(): array
    {
        $totalMappedSize = array_sum(array_column(self::$mappings, 'size'));

        return [
            'active_mappings' => count(self::$mappings),
            'total_mapped_size' => self::formatBytes($totalMappedSize),
            'mappings_created' => self::$stats['mappings_created'],
            'cache_hit_rate' => self::calculateCacheHitRate(),
            'memory_saved_estimate' => self::formatBytes(self::$stats['memory_saved']),
            'config' => self::$config,
            'detailed_stats' => self::$stats
        ];
    }

    /**
     * Calculate cache hit rate
     */
    private static function calculateCacheHitRate(): float
    {
        $totalAccess = self::$stats['cache_hits'] + self::$stats['mappings_created'];
        return $totalAccess > 0 ? (self::$stats['cache_hits'] / $totalAccess) * 100 : 0;
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
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }

    /**
     * Configure memory mapping behavior
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Reset all mappings and statistics
     */
    public static function reset(): void
    {
        self::$mappings = [];
        self::$stats = [
            'mappings_created' => 0,
            'mappings_accessed' => 0,
            'bytes_mapped' => 0,
            'memory_saved' => 0,
            'cache_hits' => 0
        ];
    }
}

/**
 * Memory Mapping Implementation
 */
class MemoryMapping
{
    private string $filePath;
    private int $offset;
    private int $length;
    private int $fileSize;
    private $handle = null;
    private array $cache = [];
    private int $cacheSize = 0;
    private const MAX_CACHE_SIZE = 1024 * 1024; // 1MB cache per mapping

    public function __construct(string $filePath, int $offset = 0, int $length = null)
    {
        $this->filePath = $filePath;
        $this->offset = $offset;
        $this->fileSize = filesize($filePath);
        $this->length = $length ?? ($this->fileSize - $offset);
    }

    /**
     * Read data from the mapped region
     */
    public function read(int $position, int $length): string
    {
        $cacheKey = $this->getCacheKey($position, $length);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        if (!$this->handle) {
            $this->handle = fopen($this->filePath, 'rb');
            if (!$this->handle) {
                return '';
            }
        }

        fseek($this->handle, $this->offset + $position);
        $data = fread($this->handle, $length);

        if ($data !== false) {
            $this->addToCache($cacheKey, $data);
        }

        return $data !== false ? $data : '';
    }

    /**
     * Prefetch data for better performance
     */
    public function prefetch(int $position, int $length): void
    {
        // Simple prefetch implementation
        $this->read($position, $length);
    }

    /**
     * Get the size of the mapped region
     */
    public function getSize(): int
    {
        return $this->length;
    }

    /**
     * Get cache key for position and length
     */
    private function getCacheKey(int $position, int $length): string
    {
        return "{$position}:{$length}";
    }

    /**
     * Add data to cache
     */
    private function addToCache(string $key, string $data): void
    {
        $dataSize = strlen($data);

        // Don't cache very large chunks
        if ($dataSize > self::MAX_CACHE_SIZE / 4) {
            return;
        }

        // Make room if cache is full
        while ($this->cacheSize + $dataSize > self::MAX_CACHE_SIZE && !empty($this->cache)) {
            $oldKey = array_key_first($this->cache);
            $this->cacheSize -= strlen($this->cache[$oldKey]);
            unset($this->cache[$oldKey]);
        }

        $this->cache[$key] = $data;
        $this->cacheSize += $dataSize;
    }

    /**
     * Close file handle
     */
    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}
