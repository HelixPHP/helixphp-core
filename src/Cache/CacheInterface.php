<?php

namespace Express\Cache;

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
    public function delete(string $key): bool;
    public function clear(): bool;
    public function has(string $key): bool;
}
