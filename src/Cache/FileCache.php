<?php

namespace Express\Cache;

/**
 * Driver de cache em arquivo
 */
class FileCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/express-cache';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * @param  mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $fileContents = file_get_contents($file);
        if ($fileContents === false) {
            return $default;
        }

        $data = unserialize($fileContents);

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
        $file = $this->getFilePath($key);
        $expires = $ttl ? time() + $ttl : null;

        $data = [
            'value' => $value,
            'expires' => $expires
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*');

        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    private function getFilePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
