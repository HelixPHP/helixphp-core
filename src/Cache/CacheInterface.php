<?php

namespace PivotPHP\Core\Cache;

/**
 * Interface para drivers de cache
 */
interface CacheInterface
{
    /**
     * @param  mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, ?int $ttl = null): bool;
    
    /**
     * Delete a cache entry by key
     */
    public function delete(string $key): bool;
    
    /**
     * Clear all cache entries
     */
    public function clear(): bool;
    
    /**
     * Check if a cache entry exists
     */
    public function has(string $key): bool;
}
