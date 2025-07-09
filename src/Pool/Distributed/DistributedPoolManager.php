<?php

declare(strict_types=1);

namespace PivotPHP\Core\Pool\Distributed;

use PivotPHP\Core\Http\Pool\DynamicPool;
use PivotPHP\Core\Pool\Distributed\Coordinators\CoordinatorInterface;
use PivotPHP\Core\Pool\Distributed\Coordinators\RedisCoordinator;

/**
 * Distributed pool management for multi-instance coordination
 */
class DistributedPoolManager
{
    /**
     * Configuration
     */
    private array $config = [
        'coordination' => 'redis',          // redis|etcd|consul
        'namespace' => 'pivotphp:pools',
        'sync_interval' => 5,               // seconds
        'leader_election' => true,
        'leader_ttl' => 30,                 // seconds
        'rebalance_interval' => 60,         // seconds
        'min_pool_size' => 10,
        'max_pool_size' => 1000,
        'borrow_timeout' => 5,              // seconds
        'health_check_interval' => 10,      // seconds
    ];

    /**
     * Instance ID
     */
    private string $instanceId;

    /**
     * Coordinator
     */
    private CoordinatorInterface $coordinator;

    /**
     * Local pool
     */
    private DynamicPool $localPool;

    /**
     * State
     */
    private array $state = [
        'is_leader' => false,
        'last_sync' => 0,
        'last_rebalance' => 0,
        'last_health_check' => 0,
        'known_instances' => [],
        'pool_distribution' => [],
    ];

    /**
     * Metrics
     */
    private array $metrics = [
        'objects_contributed' => 0,
        'objects_borrowed' => 0,
        'rebalances' => 0,
        'leader_elections' => 0,
        'sync_operations' => 0,
        'failed_borrows' => 0,
    ];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->instanceId = $this->generateInstanceId();

        // Initialize coordinator
        $this->coordinator = $this->createCoordinator();

        // Register instance
        $this->registerInstance();

        // Start background tasks
        $this->startBackgroundTasks();
    }

    /**
     * Generate instance ID
     */
    private function generateInstanceId(): string
    {
        return sprintf(
            '%s_%s_%d',
            gethostname(),
            uniqid('inst_'),
            getmypid()
        );
    }

    /**
     * Create coordinator based on configuration
     */
    private function createCoordinator(): CoordinatorInterface
    {
        return match ($this->config['coordination']) {
            'redis' => new RedisCoordinator($this->config),
            // 'etcd' => new EtcdCoordinator($this->config),
            // 'consul' => new ConsulCoordinator($this->config),
            default => throw new \InvalidArgumentException(
                "Unknown coordination backend: {$this->config['coordination']}"
            ),
        };
    }

    /**
     * Set local pool
     */
    public function setLocalPool(DynamicPool $pool): void
    {
        $this->localPool = $pool;
    }

    /**
     * Register this instance
     */
    private function registerInstance(): void
    {
        $instanceData = [
            'id' => $this->instanceId,
            'hostname' => gethostname(),
            'pid' => getmypid(),
            'started_at' => time(),
            'capabilities' => $this->getInstanceCapabilities(),
        ];

        $this->coordinator->registerInstance($this->instanceId, $instanceData);

        error_log("Distributed pool instance registered: {$this->instanceId}");
    }

    /**
     * Get instance capabilities
     */
    private function getInstanceCapabilities(): array
    {
        return [
            'memory_limit' => ini_get('memory_limit'),
            'cpu_cores' => $this->getCPUCores(),
            'pool_config' => isset($this->localPool) ? $this->localPool->getStats()['config'] : [],
        ];
    }

    /**
     * Get CPU cores
     */
    private function getCPUCores(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (int) getenv('NUMBER_OF_PROCESSORS') ?: 1;
        }

        $cores = shell_exec('nproc');
        return $cores ? (int) $cores : 1;
    }

    /**
     * Start background tasks
     */
    private function startBackgroundTasks(): void
    {
        // In a real implementation, these would be async tasks
        // For now, they'll be called periodically
        register_shutdown_function([$this, 'shutdown']);
    }

    /**
     * Contribute objects to the distributed pool
     */
    public function contribute(array $objects, string $type = 'mixed'): void
    {
        if (empty($objects)) {
            return;
        }

        $contribution = [
            'instance_id' => $this->instanceId,
            'type' => $type,
            'count' => count($objects),
            'timestamp' => microtime(true),
            'objects' => $this->serializeObjects($objects),
        ];

        $key = $this->getContributionKey($type);
        $this->coordinator->push($key, $contribution);

        $this->metrics['objects_contributed'] += count($objects);

        error_log(
            sprintf(
                "Contributed %d %s objects to distributed pool",
                count($objects),
                $type
            )
        );
    }

    /**
     * Borrow objects from the distributed pool
     */
    public function borrow(int $count, string $type = 'mixed'): array
    {
        $borrowed = [];
        $key = $this->getContributionKey($type);
        $timeout = $this->config['borrow_timeout'];
        $deadline = microtime(true) + $timeout;

        while (count($borrowed) < $count && microtime(true) < $deadline) {
            $contribution = $this->coordinator->pop($key, $timeout);

            if ($contribution === null) {
                break;
            }

            // Skip own contributions to avoid loops
            if ($contribution['instance_id'] === $this->instanceId) {
                $this->coordinator->push($key, $contribution);
                usleep(100000); // 100ms
                continue;
            }

            $objects = $this->deserializeObjects($contribution['objects']);
            $borrowed = array_merge($borrowed, array_slice($objects, 0, $count - count($borrowed)));

            // Return unused objects
            if (count($objects) > $count - count($borrowed)) {
                $unused = array_slice($objects, $count - count($borrowed));
                $contribution['objects'] = $this->serializeObjects($unused);
                $contribution['count'] = count($unused);
                $this->coordinator->push($key, $contribution);
            }
        }

        if (count($borrowed) < $count) {
            $this->metrics['failed_borrows']++;
        }

        $this->metrics['objects_borrowed'] += count($borrowed);

        return $borrowed;
    }

    /**
     * Perform pool rebalancing
     */
    public function rebalance(): void
    {
        if (!$this->shouldRebalance()) {
            return;
        }

        $this->state['last_rebalance'] = time();
        $this->metrics['rebalances']++;

        // Get all instance states
        $instances = $this->coordinator->getActiveInstances();

        if (count($instances) < 2) {
            return; // No rebalancing needed
        }

        // Calculate ideal distribution
        $distribution = $this->calculateIdealDistribution($instances);

        // Apply rebalancing
        $this->applyRebalancing($distribution);

        error_log(
            sprintf(
                "Pool rebalancing completed across %d instances",
                count($instances)
            )
        );
    }

    /**
     * Should rebalance?
     */
    private function shouldRebalance(): bool
    {
        // Only leader performs rebalancing
        if ($this->config['leader_election'] && !$this->state['is_leader']) {
            return false;
        }

        $now = time();
        return ($now - $this->state['last_rebalance']) >= $this->config['rebalance_interval'];
    }

    /**
     * Calculate ideal pool distribution
     */
    private function calculateIdealDistribution(array $instances): array
    {
        $totalCapacity = 0;
        $instanceCapacities = [];

        // Calculate total capacity
        foreach ($instances as $instance) {
            $capacity = $this->calculateInstanceCapacity($instance);
            $instanceCapacities[$instance['id']] = $capacity;
            $totalCapacity += $capacity;
        }

        if ($totalCapacity === 0) {
            return [];
        }

        // Calculate distribution percentages
        $distribution = [];
        foreach ($instanceCapacities as $instanceId => $capacity) {
            $distribution[$instanceId] = [
                'percentage' => $capacity / $totalCapacity,
                'capacity' => $capacity,
            ];
        }

        return $distribution;
    }

    /**
     * Calculate instance capacity
     */
    private function calculateInstanceCapacity(array $instance): float
    {
        $capabilities = $instance['capabilities'] ?? [];

        // Base capacity on memory and CPU
        $memoryLimit = $this->parseMemoryLimit($capabilities['memory_limit'] ?? '128M');
        $cpuCores = $capabilities['cpu_cores'] ?? 1;

        // Simple capacity formula
        $capacity = ($memoryLimit / (128 * 1024 * 1024)) * $cpuCores;

        // Adjust for instance health
        if (isset($instance['health'])) {
            $capacity *= $instance['health']['score'] ?? 1.0;
        }

        return max(0.1, $capacity);
    }

    /**
     * Parse memory limit
     */
    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 2 * 1024 * 1024 * 1024; // 2GB default
        }

        $value = (int) $limit;
        $unit = strtolower($limit[strlen($limit) - 1]);

        switch ($unit) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Apply rebalancing
     */
    private function applyRebalancing(array $distribution): void
    {
        if (!$this->localPool) {
            return;
        }

        $myDistribution = $distribution[$this->instanceId] ?? null;
        if (!$myDistribution) {
            return;
        }

        // Get current pool stats
        $stats = $this->localPool->getStats();
        $currentSize = $stats['scaling_state']['current_size'] ?? 0;

        // Calculate target size based on distribution
        $totalPoolSize = $this->coordinator->getGlobalPoolSize();
        $targetSize = (int) ($totalPoolSize * $myDistribution['percentage']);

        // Adjust within bounds
        $targetSize = max(
            $this->config['min_pool_size'],
            min($targetSize, $this->config['max_pool_size'])
        );

        if (abs($targetSize - $currentSize) > 10) {
            error_log(
                sprintf(
                    "Rebalancing pool: current=%d, target=%d (%.1f%% of global)",
                    $currentSize,
                    $targetSize,
                    $myDistribution['percentage'] * 100
                )
            );

            // Apply new size
            // Note: Would need pool method to resize
        }
    }

    /**
     * Sync with other instances
     */
    public function sync(): void
    {
        $now = time();

        if (($now - $this->state['last_sync']) < $this->config['sync_interval']) {
            return;
        }

        $this->state['last_sync'] = $now;
        $this->metrics['sync_operations']++;

        // Update instance info
        $this->updateInstanceInfo();

        // Get other instances
        $instances = $this->coordinator->getActiveInstances();
        $this->state['known_instances'] = $instances;

        // Participate in leader election if enabled
        if ($this->config['leader_election']) {
            $this->participateInLeaderElection();
        }

        // Perform rebalancing if leader
        $this->rebalance();
    }

    /**
     * Update instance information
     */
    private function updateInstanceInfo(): void
    {
        $info = [
            'id' => $this->instanceId,
            'last_seen' => time(),
            'metrics' => $this->metrics,
            'pool_stats' => $this->localPool ? $this->localPool->getStats() : [],
            'health' => $this->getHealthStatus(),
        ];

        $this->coordinator->updateInstance($this->instanceId, $info);
    }

    /**
     * Participate in leader election
     */
    private function participateInLeaderElection(): void
    {
        $wasLeader = $this->state['is_leader'];

        // Try to acquire leadership
        $this->state['is_leader'] = $this->coordinator->acquireLeadership(
            $this->instanceId,
            $this->config['leader_ttl']
        );

        // Log leadership changes
        if (!$wasLeader && $this->state['is_leader']) {
            $this->metrics['leader_elections']++;
            error_log("Instance {$this->instanceId} became leader");
        } elseif ($wasLeader && !$this->state['is_leader']) {
            error_log("Instance {$this->instanceId} lost leadership");
        }
    }

    /**
     * Get health status
     */
    private function getHealthStatus(): array
    {
        $memoryUsage = memory_get_usage(true) / $this->parseMemoryLimit(ini_get('memory_limit'));
        $poolStats = $this->localPool ? $this->localPool->getStats() : [];

        $score = 1.0;

        // Reduce score based on memory pressure
        if ($memoryUsage > 0.8) {
            $score *= 0.5;
        } elseif ($memoryUsage > 0.6) {
            $score *= 0.8;
        }

        // Reduce score based on pool stress
        if (
            isset($poolStats['stats']['emergency_activations']) &&
            $poolStats['stats']['emergency_activations'] > 0
        ) {
            $score *= 0.7;
        }

        return [
            'score' => $score,
            'memory_usage' => round($memoryUsage * 100, 2),
            'pool_healthy' => $score > 0.5,
            'last_check' => time(),
        ];
    }

    /**
     * Get contribution key
     */
    private function getContributionKey(string $type): string
    {
        return sprintf('%s:contributions:%s', $this->config['namespace'], $type);
    }

    /**
     * Serialize objects
     */
    private function serializeObjects(array $objects): string
    {
        // In real implementation, would use proper serialization
        // For now, return placeholder
        return base64_encode(serialize(count($objects)));
    }

    /**
     * Deserialize objects
     */
    private function deserializeObjects(string $data): array
    {
        // In real implementation, would deserialize actual objects
        // For now, return empty array
        $count = unserialize(base64_decode($data));
        return array_fill(0, $count, new \stdClass());
    }

    /**
     * Get status
     */
    public function getStatus(): array
    {
        return [
            'instance_id' => $this->instanceId,
            'is_leader' => $this->state['is_leader'],
            'known_instances' => count($this->state['known_instances']),
            'active_instances' => $this->getActiveInstanceCount(),
            'metrics' => $this->metrics,
            'health' => $this->getHealthStatus(),
            'coordination' => [
                'backend' => $this->config['coordination'],
                'connected' => $this->coordinator->isConnected(),
            ],
        ];
    }

    /**
     * Get active instance count
     */
    private function getActiveInstanceCount(): int
    {
        $active = 0;
        $now = time();

        foreach ($this->state['known_instances'] as $instance) {
            if (($now - $instance['last_seen']) < 30) {
                $active++;
            }
        }

        return $active;
    }

    /**
     * Get global statistics
     */
    public function getGlobalStats(): array
    {
        if (!$this->state['is_leader']) {
            return ['error' => 'Not leader'];
        }

        $instances = $this->coordinator->getActiveInstances();
        $totalContributed = 0;
        $totalBorrowed = 0;
        $totalPoolSize = 0;

        foreach ($instances as $instance) {
            $metrics = $instance['metrics'] ?? [];
            $totalContributed += $metrics['objects_contributed'] ?? 0;
            $totalBorrowed += $metrics['objects_borrowed'] ?? 0;

            $poolStats = $instance['pool_stats'] ?? [];
            foreach ($poolStats['pool_sizes'] ?? [] as $size) {
                $totalPoolSize += $size;
            }
        }

        return [
            'instances' => count($instances),
            'total_contributed' => $totalContributed,
            'total_borrowed' => $totalBorrowed,
            'total_pool_size' => $totalPoolSize,
            'balance_ratio' => $totalBorrowed > 0 ? $totalContributed / $totalBorrowed : 0,
            'distribution' => $this->state['pool_distribution'],
        ];
    }

    /**
     * Shutdown
     */
    public function shutdown(): void
    {
        // Unregister instance
        $this->coordinator->unregisterInstance($this->instanceId);

        // Release leadership if held
        if ($this->state['is_leader']) {
            $this->coordinator->releaseLeadership($this->instanceId);
        }

        error_log(
            sprintf(
                "Distributed pool instance shutting down: %s (contributed: %d, borrowed: %d)",
                $this->instanceId,
                $this->metrics['objects_contributed'],
                $this->metrics['objects_borrowed']
            )
        );
    }
}
