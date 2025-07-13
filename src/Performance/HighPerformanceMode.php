<?php

declare(strict_types=1);

namespace PivotPHP\Core\Performance;

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Pool\DynamicPoolManager;
use PivotPHP\Core\Pool\Distributed\DistributedPoolManager;
use PivotPHP\Core\Memory\MemoryManager;
use PivotPHP\Core\Middleware\TrafficClassifier;
use PivotPHP\Core\Middleware\LoadShedder;
use PivotPHP\Core\Middleware\CircuitBreaker;

/**
 * High Performance Mode configurator for PivotPHP
 */
class HighPerformanceMode
{
    /**
     * Performance profiles
     */
    public const PROFILE_STANDARD = 'standard';
    public const PROFILE_HIGH = 'high';
    public const PROFILE_EXTREME = 'extreme';
    public const PROFILE_TEST = 'test';
    public const PROFILE_CUSTOM = 'custom';

    /**
     * Profile configurations
     */
    private static array $profiles = [
        self::PROFILE_STANDARD => [
            'pool' => [
                'enable_pooling' => true,
                'initial_size' => 50,
                'max_size' => 200,
                'emergency_limit' => 300,
                'auto_scale' => true,
                'scale_threshold' => 0.7,
                'warm_up_pools' => true,
            ],
            'memory' => [
                'gc_strategy' => MemoryManager::STRATEGY_ADAPTIVE,
                'gc_threshold' => 0.7,
                'emergency_gc' => 0.85,
            ],
            'traffic' => [
                'classification' => true,
                'default_priority' => TrafficClassifier::PRIORITY_NORMAL,
            ],
            'protection' => [
                'load_shedding' => false,
                'circuit_breaker' => true,
                'circuit_threshold' => 50,
            ],
            'monitoring' => [
                'enabled' => true,
                'sample_rate' => 0.1,
                'export_interval' => 30,
            ],
        ],

        self::PROFILE_HIGH => [
            'pool' => [
                'enable_pooling' => true,
                'initial_size' => 100,
                'max_size' => 500,
                'emergency_limit' => 1000,
                'auto_scale' => true,
                'scale_threshold' => 0.6,
                'scale_factor' => 2.0,
                'warm_up_pools' => true,
            ],
            'memory' => [
                'gc_strategy' => MemoryManager::STRATEGY_ADAPTIVE,
                'gc_threshold' => 0.65,
                'emergency_gc' => 0.8,
                'check_interval' => 3,
            ],
            'traffic' => [
                'classification' => true,
                'default_priority' => TrafficClassifier::PRIORITY_NORMAL,
                'rules' => [
                    ['pattern' => '/api/critical/*', 'priority' => 'critical'],
                    ['pattern' => '/api/batch/*', 'priority' => 'low'],
                    ['pattern' => '/health', 'priority' => 'system'],
                ],
            ],
            'protection' => [
                'load_shedding' => true,
                'shed_strategy' => LoadShedder::STRATEGY_PRIORITY,
                'max_concurrent' => 5000,
                'circuit_breaker' => true,
                'circuit_threshold' => 100,
            ],
            'monitoring' => [
                'enabled' => true,
                'sample_rate' => 0.2,
                'export_interval' => 10,
                'alert_thresholds' => [
                    'latency_p99' => 500,
                    'error_rate' => 0.02,
                ],
            ],
            'distributed' => [
                'enabled' => false,
            ],
        ],

        self::PROFILE_EXTREME => [
            'pool' => [
                'enable_pooling' => true,
                'initial_size' => 200,
                'max_size' => 1000,
                'emergency_limit' => 2000,
                'auto_scale' => true,
                'scale_threshold' => 0.5,
                'scale_factor' => 2.5,
                'shrink_threshold' => 0.2,
                'warm_up_pools' => true,
            ],
            'memory' => [
                'gc_strategy' => MemoryManager::STRATEGY_AGGRESSIVE,
                'gc_threshold' => 0.6,
                'emergency_gc' => 0.75,
                'check_interval' => 1,
                'object_lifetime' => [
                    'request' => 120,
                    'response' => 120,
                    'stream' => 30,
                ],
            ],
            'traffic' => [
                'classification' => true,
                'default_priority' => TrafficClassifier::PRIORITY_NORMAL,
                'rules' => [
                    ['pattern' => '/api/critical/*', 'priority' => 'critical'],
                    ['pattern' => '/api/admin/*', 'priority' => 'high'],
                    ['pattern' => '/api/batch/*', 'priority' => 'batch'],
                    ['pattern' => '/api/analytics/*', 'priority' => 'low'],
                    ['pattern' => '/health', 'priority' => 'system'],
                    ['pattern' => '/metrics', 'priority' => 'system'],
                ],
            ],
            'protection' => [
                'load_shedding' => true,
                'shed_strategy' => LoadShedder::STRATEGY_ADAPTIVE,
                'max_concurrent' => 10000,
                'activation_threshold' => 0.8,
                'deactivation_threshold' => 0.6,
                'circuit_breaker' => true,
                'circuit_threshold' => 200,
                'circuit_timeout' => 15,
                'half_open_requests' => 20,
            ],
            'monitoring' => [
                'enabled' => true,
                'sample_rate' => 0.5,
                'export_interval' => 5,
                'percentiles' => [50, 90, 95, 99, 99.9],
                'alert_thresholds' => [
                    'latency_p99' => 200,
                    'error_rate' => 0.01,
                    'memory_usage' => 0.7,
                    'gc_frequency' => 50,
                ],
            ],
            'distributed' => [
                'enabled' => true,
                'coordination' => 'redis',
                'sync_interval' => 3,
                'leader_election' => true,
                'rebalance_interval' => 30,
            ],
        ],

        self::PROFILE_TEST => [
            'pool' => [
                'enable_pooling' => false,      // Disable pooling for test speed
                'initial_size' => 0,
                'max_size' => 0,
                'auto_scale' => false,
                'warm_up_pools' => false,
            ],
            'memory' => [
                'gc_strategy' => MemoryManager::STRATEGY_CONSERVATIVE,
                'gc_threshold' => 0.9,          // Higher threshold for tests
                'emergency_gc' => 0.95,
                'check_interval' => 60,         // Less frequent checks
            ],
            'traffic' => [
                'classification' => false,      // No traffic classification in tests
            ],
            'protection' => [
                'load_shedding' => false,       // No load shedding in tests
                'circuit_breaker' => false,     // No circuit breakers in tests
            ],
            'monitoring' => [
                'enabled' => false,             // Minimal monitoring for tests
                'sample_rate' => 0,
                'export_interval' => 300,       // Rarely export
            ],
            'distributed' => [
                'enabled' => false,             // No distributed features in tests
            ],
        ],
    ];

    /**
     * Current configuration
     */
    private static array $currentConfig = [];

    /**
     * Components
     */
    private static ?DynamicPoolManager $pool = null;
    private static ?MemoryManager $memoryManager = null;
    private static ?PerformanceMonitor $monitor = null;
    private static ?DistributedPoolManager $distributedManager = null;

    /**
     * Enable high performance mode
     */
    public static function enable(
        string|array $profileOrConfig = self::PROFILE_HIGH,
        ?Application $app = null
    ): void {
        // Load configuration
        if (is_string($profileOrConfig)) {
            if (!isset(self::$profiles[$profileOrConfig])) {
                throw new \InvalidArgumentException("Unknown profile: $profileOrConfig");
            }
            self::$currentConfig = self::$profiles[$profileOrConfig];
        } else {
            self::$currentConfig = array_merge_recursive(
                self::$profiles[self::PROFILE_HIGH],
                $profileOrConfig
            );
        }

        // Initialize components
        self::initializePooling();
        self::initializeMemoryManagement();

        if ($app !== null) {
            self::initializeTrafficManagement($app);
            self::initializeProtection($app);
            self::initializeMonitoring($app);

            // Set application to high performance mode
            $app->setConfig('high_performance', true);
            $app->setConfig('performance_profile', is_string($profileOrConfig) ? $profileOrConfig : 'custom');
        } else {
            // Initialize monitoring without app
            self::initializeMonitoring(null);
        }

        if (self::$currentConfig['distributed']['enabled'] ?? false) {
            self::initializeDistributed();
        }

        // High Performance Mode enabled - logging removed for clean test output
    }

    /**
     * Initialize pooling
     */
    private static function initializePooling(): void
    {
        $poolConfig = self::$currentConfig['pool'];

        // Create dynamic pool
        self::$pool = new DynamicPoolManager($poolConfig);

        // Configure optimized factory
        OptimizedHttpFactory::initialize($poolConfig);

        // Set pool reference in factory if needed
        // OptimizedHttpFactory::setPool(self::$pool);
    }

    /**
     * Initialize memory management
     */
    private static function initializeMemoryManagement(): void
    {
        $memoryConfig = self::$currentConfig['memory'];

        self::$memoryManager = new MemoryManager($memoryConfig);

        if (self::$pool) {
            self::$memoryManager->setPool(self::$pool);
        }

        // Start periodic memory checks
        self::schedulePeriodicTask(
            $memoryConfig['check_interval'] ?? 5,
            [self::$memoryManager, 'check']
        );
    }

    /**
     * Initialize traffic management
     */
    private static function initializeTrafficManagement(Application $app): void
    {
        if (!self::$currentConfig['traffic']['classification']) {
            return;
        }

        $classifier = new TrafficClassifier(self::$currentConfig['traffic']);

        // Register as early middleware
        $app->use($classifier);
    }

    /**
     * Initialize protection middlewares
     */
    private static function initializeProtection(Application $app): void
    {
        $protection = self::$currentConfig['protection'];

        // Circuit breaker
        if ($protection['circuit_breaker']) {
            $circuitConfig = [
                'failure_threshold' => $protection['circuit_threshold'] ?? 50,
                'timeout' => $protection['circuit_timeout'] ?? 30,
                'half_open_requests' => $protection['half_open_requests'] ?? 10,
            ];

            $app->use(new CircuitBreaker($circuitConfig));
        }

        // Load shedder
        if ($protection['load_shedding']) {
            $shedConfig = [
                'max_concurrent_requests' => $protection['max_concurrent'] ?? 5000,
                'shed_strategy' => $protection['shed_strategy'] ?? LoadShedder::STRATEGY_PRIORITY,
                'activation_threshold' => $protection['activation_threshold'] ?? 0.9,
                'deactivation_threshold' => $protection['deactivation_threshold'] ?? 0.7,
            ];

            $app->use(new LoadShedder($shedConfig));
        }
    }

    /**
     * Initialize monitoring
     */
    private static function initializeMonitoring(?Application $app): void
    {
        if (!self::$currentConfig['monitoring']['enabled']) {
            return;
        }

        self::$monitor = new PerformanceMonitor(self::$currentConfig['monitoring']);

        // Register monitoring middleware only if app is provided
        if ($app !== null) {
            $app->use(
                function ($request, $response, $next) {
                    $requestId = uniqid('req_', true);

                // Start monitoring
                    self::$monitor?->startRequest(
                        $requestId,
                        [
                            'path' => $request->pathCallable,
                            'method' => $request->method,
                            'priority' => $request->getAttribute('traffic_priority'),
                        ]
                    );

                    try {
                        $result = $next($request, $response);

                        // End monitoring
                        self::$monitor?->endRequest($requestId, $response->getStatusCode());

                        return $result;
                    } catch (\Throwable $e) {
                        // Record error
                        self::$monitor?->recordError(
                            'exception',
                            [
                                'message' => $e->getMessage(),
                                'code' => $e->getCode(),
                            ]
                        );

                        self::$monitor?->endRequest($requestId, 500);

                        throw $e;
                    }
                }
            );
        }

        // Schedule periodic tasks
        self::schedulePeriodicTask(
            self::$currentConfig['monitoring']['export_interval'],
            [self::$monitor, 'export']
        );
    }

    /**
     * Initialize distributed pooling
     */
    private static function initializeDistributed(): void
    {
        $config = self::$currentConfig['distributed'];

        try {
            self::$distributedManager = new DistributedPoolManager($config);

            if (self::$pool) {
                self::$distributedManager->setLocalPool(self::$pool);
            }

            // Schedule sync tasks
            self::schedulePeriodicTask(
                $config['sync_interval'] ?? 5,
                [self::$distributedManager, 'sync']
            );
        } catch (\Exception $e) {
            error_log('Failed to initialize distributed pooling: ' . $e->getMessage());
            // Distributed pooling is optional, continue without it
            self::$distributedManager = null;
        }
    }

    /**
     * Schedule periodic task (simulated)
     */
    private static function schedulePeriodicTask(int $interval, callable $task): void
    {
        // In real implementation, would use async task scheduler
        // For now, tasks are called manually or via cron
        register_tick_function(
            function () use ($interval, $task) {
                static $lastRun = [];
                $key = spl_object_hash((object) $task);

                if (!isset($lastRun[$key])) {
                    $lastRun[$key] = time();
                }

                if (time() - $lastRun[$key] >= $interval) {
                    try {
                        $task();
                    } catch (\Exception $e) {
                        error_log("Periodic task failed: " . $e->getMessage());
                    }
                    $lastRun[$key] = time();
                }
            }
        );
    }

    /**
     * Get monitor instance
     */
    public static function getMonitor(): ?PerformanceMonitor
    {
        return self::$monitor;
    }

    /**
     * Get current status
     */
    public static function getStatus(): array
    {
        return [
            'enabled' => !empty(self::$currentConfig),
            'profile' => self::$currentConfig['profile'] ?? 'custom',
            'components' => [
                'pooling' => self::$pool !== null,
                'memory_management' => self::$memoryManager !== null,
                'monitoring' => self::$monitor !== null,
                'distributed' => self::$distributedManager !== null,
            ],
            'pool_stats' => self::$pool?->getStats() ?? [],
            'memory_status' => self::$memoryManager?->getStatus() ?? [],
            'monitor_metrics' => self::$monitor?->getLiveMetrics() ?? [],
            'distributed_status' => self::$distributedManager?->getStatus() ?? [],
        ];
    }

    /**
     * Get performance report
     */
    public static function getPerformanceReport(): array
    {
        if (!self::$monitor) {
            return ['error' => 'Monitoring not enabled'];
        }

        $metrics = self::$monitor->getPerformanceMetrics();
        $poolStats = self::$pool?->getStats() ?? [];
        $memoryStatus = self::$memoryManager?->getStatus() ?? [];

        return [
            'timestamp' => microtime(true),
            'profile' => self::$currentConfig['profile'] ?? 'custom',
            'performance' => $metrics,
            'pool' => [
                'efficiency' => $poolStats['metrics']['pool_efficiency'] ?? [],
                'usage' => $poolStats['pool_usage'] ?? [],
                'scaling' => $poolStats['scaling_state'] ?? [],
            ],
            'memory' => [
                'pressure' => $memoryStatus['pressure'] ?? 'unknown',
                'usage_percent' => $memoryStatus['usage']['percentage'] ?? 0,
                'gc_runs' => $memoryStatus['gc']['runs'] ?? 0,
            ],
            'recommendations' => self::generateRecommendations($metrics, $poolStats, $memoryStatus),
        ];
    }

    /**
     * Generate recommendations
     */
    private static function generateRecommendations(
        array $metrics,
        array $poolStats,
        array $memoryStatus
    ): array {
        $recommendations = [];

        // Latency recommendations
        if (($metrics['latency']['p99'] ?? 0) > 1000) {
            $recommendations[] = [
                'type' => 'performance',
                'severity' => 'high',
                'message' => 'P99 latency exceeds 1 second - consider scaling up',
            ];
        }

        // Memory recommendations
        if (($memoryStatus['usage']['percentage'] ?? 0) > 80) {
            $recommendations[] = [
                'type' => 'memory',
                'severity' => 'high',
                'message' => 'Memory usage above 80% - enable more aggressive GC',
            ];
        }

        // Pool recommendations
        foreach ($poolStats['metrics']['pool_efficiency'] ?? [] as $type => $efficiency) {
            if ($efficiency < 50) {
                $recommendations[] = [
                    'type' => 'pool',
                    'severity' => 'medium',
                    'message' => "Low $type pool efficiency ($efficiency%) - adjust pool size",
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Adjust configuration dynamically
     */
    public static function adjustConfig(array $adjustments): void
    {
        self::$currentConfig = array_merge_recursive(self::$currentConfig, $adjustments);

        // Apply adjustments to components
        // This would need component-specific update methods

        // Configuration adjusted - logging removed for clean test output
    }

    /**
     * Disable high performance mode
     */
    public static function disable(): void
    {
        // Clean up components
        self::$pool = null;
        self::$memoryManager = null;
        self::$monitor = null;
        self::$distributedManager = null;

        // Reset configuration
        self::$currentConfig = [];

        // Disable in factory
        OptimizedHttpFactory::initialize(['enable_pooling' => false]);

        // High performance mode disabled - logging removed for clean test output
    }
}
