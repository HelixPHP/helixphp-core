<?php

namespace Express\Cache;

/**
 * Driver de cache em memória (para sessão atual)
 */
class MemoryCache implements CacheInterface
{
    private array $cache = [];

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

    public function set(string $key, $value, int $ttl = null): bool
    {
        $expires = $ttl ? time() + $ttl : null;

        $this->cache[$key] = [
            'value' => $value,
            'expires' => $expires
        ];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->cache[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }
}
