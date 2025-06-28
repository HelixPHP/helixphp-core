<?php

declare(strict_types=1);

namespace Express\Http\Optimization;

/**
 * Memory mapping implementation for efficient file operations
 */
class MemoryMapping
{
    private string $filePath;
    private int $offset;
    private int $length;
    private int $fileSize;
    /** @var resource|null */
    private $handle = null;
    private array $cache = [];
    private int $cacheSize = 0;
    private const MAX_CACHE_SIZE = 1024 * 1024; // 1MB cache per mapping

    public function __construct(string $filePath, int $offset = 0, ?int $length = null)
    {
        $this->filePath = $filePath;
        $this->offset = $offset;
        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new \RuntimeException("Cannot get file size for: {$filePath}");
        }
        $this->fileSize = $fileSize;
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
            $resource = fopen($this->filePath, 'rb');
            if ($resource === false) {
                return '';
            }
            $this->handle = $resource;
        }

        fseek($this->handle, $this->offset + $position);
        $data = fread($this->handle, max(1, $length));

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
