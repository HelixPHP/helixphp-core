<?php

namespace PivotPHP\Core\Cache;

/**
 * Driver de cache em memória (para sessão atual)
 */
class MemoryCache implements CacheInterface
{
    private array $cache = [];

    /**
     * @param  mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        $data = $this->cache[$key];

        if ($data['expires'] && time() > $data['expires']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $expires = $ttl ? time() + $ttl : null;

        $this->cache[$key] = [
            'value' => $value,
            'expires' => $expires
        ];

        return true;
    }

    /**
     * Delete a cache entry by key
     */
    public function delete(string $key): bool
    {
        unset($this->cache[$key]);
        return true;
    }

    /**
     * Clear all cache entries
     */
    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    /**
     * Check if a cache entry exists
     */
    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }
}
