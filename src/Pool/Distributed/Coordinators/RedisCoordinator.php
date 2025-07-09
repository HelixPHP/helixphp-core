<?php

declare(strict_types=1);

namespace PivotPHP\Core\Pool\Distributed\Coordinators;

/**
 * Redis-based coordinator for distributed pool management
 */
class RedisCoordinator implements CoordinatorInterface
{
    /**
     * Redis client
     */
    private ?\Redis $redis = null;

    /**
     * Configuration
     */
    private array $config;

    /**
     * Connection state
     */
    private bool $connected = false;

    /**
     * Key prefixes
     */
    private const PREFIX_INSTANCE = 'instance:';
    private const PREFIX_QUEUE = 'queue:';
    private const PREFIX_LEADER = 'leader';
    private const PREFIX_GLOBAL = 'global:';

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Connect to Redis
     */
    private function connect(): void
    {
        try {
            // Check if Redis extension is loaded
            if (!extension_loaded('redis')) {
                error_log("Redis extension not loaded - distributed pooling disabled");
                return;
            }

            $this->redis = new \Redis();

            $host = $this->config['redis_host'] ?? '127.0.0.1';
            $port = $this->config['redis_port'] ?? 6379;
            $timeout = $this->config['redis_timeout'] ?? 2.0;

            $this->connected = $this->redis->connect($host, $port, $timeout);

            if ($this->connected) {
                // Set key prefix
                $this->redis->setOption(\Redis::OPT_PREFIX, $this->config['namespace'] . ':');

                // Authentication if needed
                if (isset($this->config['redis_password'])) {
                    $this->redis->auth($this->config['redis_password']);
                }

                // Select database
                if (isset($this->config['redis_database'])) {
                    $this->redis->select($this->config['redis_database']);
                }

                error_log("Connected to Redis for distributed pool coordination");
            }
        } catch (\Exception $e) {
            error_log("Failed to connect to Redis: " . $e->getMessage());
            $this->connected = false;
        }
    }

    /**
     * Check connection and reconnect if needed
     */
    private function ensureConnected(): bool
    {
        if (!$this->redis || !$this->connected) {
            return false;
        }

        try {
            $this->redis->ping();
            return true;
        } catch (\Exception $e) {
            $this->connected = false;
            $this->connect();
            return $this->connected;
        }
    }

    /**
     * Register an instance
     */
    public function registerInstance(string $instanceId, array $data): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        try {
            $key = self::PREFIX_INSTANCE . $instanceId;
            $data['last_seen'] = time();

            return $this->redis->setex(
                $key,
                60, // 60 second TTL
                json_encode($data)
            );
        } catch (\Exception $e) {
            error_log("Failed to register instance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update instance information
     */
    public function updateInstance(string $instanceId, array $data): bool
    {
        return $this->registerInstance($instanceId, $data);
    }

    /**
     * Unregister an instance
     */
    public function unregisterInstance(string $instanceId): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        try {
            $key = self::PREFIX_INSTANCE . $instanceId;
            return $this->redis->del($key) > 0;
        } catch (\Exception $e) {
            error_log("Failed to unregister instance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all active instances
     */
    public function getActiveInstances(): array
    {
        if (!$this->ensureConnected()) {
            return [];
        }

        try {
            $pattern = self::PREFIX_INSTANCE . '*';
            $keys = $this->redis->keys($pattern);

            if (empty($keys)) {
                return [];
            }

            $instances = [];
            foreach ($keys as $key) {
                $data = $this->redis->get($key);
                if ($data) {
                    $instance = json_decode($data, true);
                    if ($instance) {
                        $instances[] = $instance;
                    }
                }
            }

            return $instances;
        } catch (\Exception $e) {
            error_log("Failed to get active instances: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Push data to a queue
     */
    public function push(string $key, array $data): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        try {
            $queueKey = self::PREFIX_QUEUE . $key;
            return $this->redis->lPush($queueKey, json_encode($data)) !== false;
        } catch (\Exception $e) {
            error_log("Failed to push to queue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pop data from a queue
     */
    public function pop(string $key, int $timeout = 0): ?array
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        try {
            $queueKey = self::PREFIX_QUEUE . $key;

            if ($timeout > 0) {
                $result = $this->redis->brPop([$queueKey], $timeout);
                if ($result && isset($result[1])) {
                    return json_decode($result[1], true);
                }
            } else {
                $result = $this->redis->rPop($queueKey);
                if ($result) {
                    return json_decode($result, true);
                }
            }

            return null;
        } catch (\Exception $e) {
            error_log("Failed to pop from queue: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Acquire leadership
     */
    public function acquireLeadership(string $instanceId, int $ttl): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        try {
            $key = self::PREFIX_LEADER;

            // Try to acquire lock with NX (only if not exists)
            $result = $this->redis->set($key, $instanceId, ['nx', 'ex' => $ttl]);

            if ($result) {
                return true;
            }

            // Check if we already have leadership
            $current = $this->redis->get($key);
            if ($current === $instanceId) {
                // Extend TTL
                $this->redis->expire($key, $ttl);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log("Failed to acquire leadership: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Release leadership
     */
    public function releaseLeadership(string $instanceId): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        try {
            $key = self::PREFIX_LEADER;

            // Only delete if we are the leader
            $current = $this->redis->get($key);
            if ($current === $instanceId) {
                return $this->redis->del($key) > 0;
            }

            return false;
        } catch (\Exception $e) {
            error_log("Failed to release leadership: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current leader
     */
    public function getCurrentLeader(): ?string
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        try {
            $key = self::PREFIX_LEADER;
            $leader = $this->redis->get($key);

            return $leader ?: null;
        } catch (\Exception $e) {
            error_log("Failed to get current leader: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get global pool size
     */
    public function getGlobalPoolSize(): int
    {
        if (!$this->ensureConnected()) {
            return 0;
        }

        try {
            $key = self::PREFIX_GLOBAL . 'pool_size';
            $size = $this->redis->get($key);

            return $size ? (int) $size : 0;
        } catch (\Exception $e) {
            error_log("Failed to get global pool size: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Set a value with TTL
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        try {
            $serialized = is_string($value) ? $value : json_encode($value);

            if ($ttl > 0) {
                return $this->redis->setex($key, $ttl, $serialized);
            } else {
                return $this->redis->set($key, $serialized);
            }
        } catch (\Exception $e) {
            error_log("Failed to set value: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a value
     */
    public function get(string $key): mixed
    {
        if (!$this->ensureConnected()) {
            return null;
        }

        try {
            $value = $this->redis->get($key);

            if ($value === false) {
                return null;
            }

            // Try to decode JSON
            $decoded = json_decode($value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        } catch (\Exception $e) {
            error_log("Failed to get value: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a key
     */
    public function delete(string $key): bool
    {
        if (!$this->ensureConnected()) {
            return false;
        }

        try {
            return $this->redis->del($key) > 0;
        } catch (\Exception $e) {
            error_log("Failed to delete key: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected && $this->ensureConnected();
    }

    /**
     * Update global pool size
     */
    public function updateGlobalPoolSize(int $delta): void
    {
        if (!$this->ensureConnected()) {
            return;
        }

        try {
            $key = self::PREFIX_GLOBAL . 'pool_size';

            if ($delta > 0) {
                $this->redis->incrBy($key, $delta);
            } elseif ($delta < 0) {
                $this->redis->decrBy($key, abs($delta));
            }

            // Set expiry to prevent stale data
            $this->redis->expire($key, 300); // 5 minutes
        } catch (\Exception $e) {
            error_log("Failed to update global pool size: " . $e->getMessage());
        }
    }

    /**
     * Get queue length
     */
    public function getQueueLength(string $key): int
    {
        if (!$this->ensureConnected()) {
            return 0;
        }

        try {
            $queueKey = self::PREFIX_QUEUE . $key;
            return (int) $this->redis->lLen($queueKey);
        } catch (\Exception $e) {
            error_log("Failed to get queue length: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cleanup expired data
     */
    public function cleanup(): void
    {
        // Redis handles expiration automatically
        // This method is here for interface compatibility
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->redis && $this->connected) {
            try {
                $this->redis->close();
            } catch (\Exception $e) {
                // Ignore errors on close
            }
        }
    }
}
