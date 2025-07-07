<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Optimization;

/**
 * Memory Mapping Manager for Large Files
 *
 * Provides memory-mapped file I/O for efficient handling of large files
 * without loading entire contents into memory.
 *
 * @package PivotPHP\Core\Http\Optimization
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
    public static function createMapping(string $filePath, int $offset = 0, ?int $length = null): ?MemoryMapping
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
    public static function getMapping(string $filePath, int $offset = 0, ?int $length = null): ?MemoryMapping
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
     * @param resource $output
     */
    public static function streamFile(string $filePath, $output, ?int $chunkSize = null): int
    {
        $chunkSize = $chunkSize ?? self::$config['chunk_size'];
        $mapping = self::createMapping($filePath);

        if (!$mapping) {
            // Fallback to regular file streaming
            $safeChunkSize = is_int($chunkSize) ? max(1, $chunkSize) : 8192;
            return self::fallbackStreamFile($filePath, $output, $safeChunkSize);
        }

        $totalBytes = 0;
        $fileSize = $mapping->getSize();
        $position = 0;

        while ($position < $fileSize) {
            $currentChunkSize = min($chunkSize, $fileSize - $position);
            $chunk = $mapping->read($position, $currentChunkSize);

            if ($chunk !== false && $chunk !== '') {
                $bytesWritten = fwrite($output, $chunk);
                $totalBytes += $bytesWritten;
                $position += strlen($chunk);

                // Prefetch next chunk if enabled
                if (self::$config['enable_prefetch'] && ($position + $chunkSize) < $fileSize) {
                    $mapping->prefetch($position, min($chunkSize, $fileSize - $position));
                }
            } else {
                break;
            }
        }

        self::$stats['mappings_accessed']++;
        return $totalBytes;
    }

    /**
     * Fallback streaming for files that can't be memory mapped
     *
     * @param resource $output Output stream
     */
    private static function fallbackStreamFile(string $filePath, $output, int $chunkSize): int
    {
        $totalBytes = 0;
        $handle = fopen($filePath, 'rb');

        if (!$handle) {
            return 0;
        }

        while (!feof($handle)) {
            $chunk = fread($handle, max(1, $chunkSize));
            if ($chunk !== false && $chunk !== '') {
                $bytesWritten = fwrite($output, $chunk);
                $totalBytes += $bytesWritten;
            } else {
                break;
            }
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
            $data = fread($handle, max(1, $length));
            fclose($handle);

            return ($data !== false && $data !== '') ? $data : null;
        }

        return $mapping->read(0, $length);
    }

    /**
     * Search in large file without loading into memory
     */
    public static function searchInFile(string $filePath, string $needle, ?int $chunkSize = null): array
    {
        $chunkSize = $chunkSize ?? self::$config['chunk_size'];
        $safeChunkSize = is_int($chunkSize) ? max(1, $chunkSize) : 8192;
        $mapping = self::createMapping($filePath);
        $matches = [];

        if (!$mapping) {
            return self::fallbackSearchInFile($filePath, $needle, $safeChunkSize);
        }

        $fileSize = $mapping->getSize();
        $position = 0;
        $overlap = strlen($needle) - 1;

        while ($position < $fileSize) {
            $currentChunkSize = min($chunkSize, $fileSize - $position);
            $chunk = $mapping->read($position, $currentChunkSize);

            if ($chunk !== false && $chunk !== '') {
                // Find all occurrences in this chunk
                $offset = 0;
                while (($pos = strpos($chunk, $needle, $offset)) !== false) {
                    $matches[] = $position + $pos;
                    $offset = $pos + 1;
                }
            } else {
                break;
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
            $chunk = fread($handle, max(1, $chunkSize));
            if ($chunk !== false && $chunk !== '') {
                $searchText = $buffer . $chunk;
                $offset = 0;

                while (($pos = strpos($searchText, $needle, $offset)) !== false) {
                    $matches[] = $position - strlen($buffer) + $pos;
                    $offset = $pos + 1;
                }

                // Keep overlap for next iteration
                $buffer = substr($searchText, -$overlap);
                $position += strlen($chunk);
            } else {
                break;
            }
        }

        fclose($handle);
        return $matches;
    }

    /**
     * Process large file line by line without loading into memory
     */
    public static function processFileLines(string $filePath, callable $processor, ?int $bufferSize = null): int
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

            if ($chunk !== false && $chunk !== '') {
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
            } else {
                break;
            }
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
            uasort(
                self::$mappings,
                function ($a, $b) {
                    return $a['last_accessed'] <=> $b['last_accessed'];
                }
            );

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
            'total_mapped_size' => \PivotPHP\Core\Utils\Utils::formatBytes($totalMappedSize),
            'mappings_created' => self::$stats['mappings_created'],
            'cache_hit_rate' => self::calculateCacheHitRate(),
            'memory_saved_estimate' => \PivotPHP\Core\Utils\Utils::formatBytes(self::$stats['memory_saved']),
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
