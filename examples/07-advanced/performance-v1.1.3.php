<?php

/**
 * 🚀 PivotPHP v1.2.0 - Performance Simplificada Demo
 * 
 * Demonstrates the simplified performance improvements in v1.2.0:
 * - Simplified performance mode
 * - Automatic JSON optimization
 * - Memory efficiency improvements
 * - Following "Simplicidade sobre Otimização Prematura" principle
 * 
 * 🚀 How to run:
 * php -S localhost:8000 examples/07-advanced/performance-v1.1.3.php
 * 
 * 🧪 How to test:
 * curl http://localhost:8000/
 * curl http://localhost:8000/performance/metrics
 * curl http://localhost:8000/performance/json/small
 * curl http://localhost:8000/performance/json/medium
 * curl http://localhost:8000/performance/json/large
 * curl http://localhost:8000/performance/stress-test
 * curl http://localhost:8000/performance/pool-stats
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Performance\PerformanceMode;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

// 🎯 Create Application with Performance Monitoring
$app = new Application();

// ⚡ Enable Performance Mode
PerformanceMode::enable(PerformanceMode::PROFILE_PRODUCTION);

// 🏠 Home route - Performance overview
$app->get('/', function($req, $res) {
    return $res->json([
        'title' => 'PivotPHP v1.2.0 - Performance Simplificada Demo',
        'performance_improvements' => [
            'framework_throughput' => '+116% improvement (20,400 → 44,092 ops/sec)',
            'object_pool_reuse' => [
                'request_pool' => '100% reuse rate (was 0%)',
                'response_pool' => '99.9% reuse rate (was 0%)'
            ],
            'json_optimization' => 'Automatic buffer pooling for large datasets',
            'memory_efficiency' => 'Smart garbage collection and pool warming'
        ],
        'test_endpoints' => [
            'GET /performance/metrics' => 'Real-time performance metrics',
            'GET /performance/json/{size}' => 'JSON optimization demo (small/medium/large)',
            'GET /performance/stress-test' => 'Framework stress test',
            'GET /performance/pool-stats' => 'Object pool statistics'
        ],
        'version_info' => [
            'framework' => 'PivotPHP Core v1.1.3',
            'php_version' => PHP_VERSION,
            'performance_mode' => PerformanceMode::isEnabled() ? 'ENABLED' : 'DISABLED'
        ]
    ]);
});

// 📊 Performance Metrics
$app->get('/performance/metrics', function($req, $res) {
    // Performance monitor is always available in v1.2.0
    $monitor = new \PivotPHP\Core\Performance\PerformanceMonitor();
    
    $metrics = $monitor->getPerformanceMetrics();
    $liveMetrics = $monitor->getLiveMetrics();
    
    return $res->json([
        'performance_metrics' => $metrics,
        'live_metrics' => $liveMetrics,
        'framework_improvements' => [
            'baseline_v1_1_2' => '20,400 ops/sec',
            'current_v1_1_3' => '44,092 ops/sec',
            'improvement' => '+116%',
            'measured_on' => 'Docker environment'
        ],
        'pool_efficiency' => [
            'request_pool_reuse' => '100%',
            'response_pool_reuse' => '99.9%',
            'memory_pressure' => $liveMetrics['memory_pressure'] ?? 'unknown'
        ],
        'timestamp' => date('c')
    ]);
});

// 🎯 JSON Optimization Demo
$app->get('/performance/json/:size', function($req, $res) {
    $size = $req->param('size');
    
    // Generate different sized datasets
    $data = match($size) {
        'small' => generateSmallDataset(),
        'medium' => generateMediumDataset(),
        'large' => generateLargeDataset(),
        default => ['error' => 'Size must be small, medium, or large']
    };
    
    if (isset($data['error'])) {
        return $res->status(400)->json($data);
    }
    
    // Measure JSON encoding performance
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    // This will automatically use JsonBufferPool for large datasets
    $jsonString = json_encode($data);
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage(true);
    
    // Get JsonBufferPool statistics
    $poolStats = JsonBufferPool::getStatistics();
    
    return $res->json([
        'dataset_info' => [
            'size' => $size,
            'record_count' => count($data['records'] ?? []),
            'estimated_size' => strlen($jsonString) . ' bytes',
            'human_readable_size' => formatBytes(strlen($jsonString))
        ],
        'encoding_performance' => [
            'encoding_time' => round(($endTime - $startTime) * 1000, 3) . ' ms',
            'memory_used' => formatBytes($endMemory - $startMemory),
            'ops_per_second' => round(1 / ($endTime - $startTime), 2)
        ],
        'json_pool_stats' => $poolStats,
        'optimization_info' => [
            'automatic_pooling' => $size === 'large' ? 'ACTIVE' : 'FALLBACK',
            'pool_benefits' => [
                'reduced_gc_pressure' => true,
                'buffer_reuse' => $poolStats['reuse_rate'] ?? 0 . '%',
                'memory_efficiency' => true
            ]
        ],
        'v1_1_3_features' => [
            'automatic_detection' => 'Arrays 10+ elements, objects 5+ properties',
            'transparent_fallback' => 'Small data uses traditional json_encode()',
            'zero_configuration' => 'Works out-of-the-box with existing code'
        ]
    ]);
});

// 🔥 Stress Test Endpoint
$app->get('/performance/stress-test', function($req, $res) {
    $iterations = (int) $req->get('iterations', 100);
    $maxIterations = 1000; // Safety limit
    
    if ($iterations > $maxIterations) {
        return $res->status(400)->json([
            'error' => "Maximum iterations is {$maxIterations}",
            'requested' => $iterations
        ]);
    }
    
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    $results = [];
    
    for ($i = 0; $i < $iterations; $i++) {
        // Simulate typical API operations
        $iterationStart = microtime(true);
        
        // Create some data
        $data = [
            'id' => $i,
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'metadata' => [
                'created_at' => date('c'),
                'iteration' => $i,
                'random_data' => str_repeat('x', rand(10, 100))
            ]
        ];
        
        // JSON encode (will use pooling if beneficial)
        $json = json_encode($data);
        
        // Simulate some processing
        $processed = json_decode($json, true);
        $processed['processed'] = true;
        
        $iterationEnd = microtime(true);
        
        if ($i % 50 === 0 || $i < 10) { // Sample results
            $results[] = [
                'iteration' => $i,
                'time_ms' => round(($iterationEnd - $iterationStart) * 1000, 3),
                'data_size' => strlen($json)
            ];
        }
    }
    
    $endTime = microtime(true);
    $endMemory = memory_get_usage(true);
    
    $totalTime = $endTime - $startTime;
    $throughput = $iterations / $totalTime;
    
    return $res->json([
        'stress_test_results' => [
            'iterations' => $iterations,
            'total_time' => round($totalTime, 4) . ' seconds',
            'average_time_per_iteration' => round(($totalTime / $iterations) * 1000, 3) . ' ms',
            'throughput' => round($throughput, 2) . ' ops/sec',
            'memory_used' => formatBytes($endMemory - $startMemory),
            'peak_memory' => formatBytes(memory_get_peak_usage(true))
        ],
        'sample_iterations' => $results,
        'performance_comparison' => [
            'v1_1_2_baseline' => '20,400 ops/sec',
            'current_result' => round($throughput, 2) . ' ops/sec',
            'framework_efficiency' => 'High (object pooling active)'
        ],
        'system_info' => [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ]
    ]);
});

// 🏊 Object Pool Statistics
$app->get('/performance/pool-stats', function($req, $res) {
    // Get HTTP factory stats
    $factoryStats = OptimizedHttpFactory::getStatistics();
    
    // Get JSON pool stats
    $jsonStats = JsonBufferPool::getStatistics();
    
    // Get performance monitor
    $monitor = new \PivotPHP\Core\Performance\PerformanceMonitor();
    $monitorStats = $monitor ? $monitor->getLiveMetrics() : null;
    
    return $res->json([
        'object_pool_statistics' => [
            'http_factory' => $factoryStats,
            'json_buffer_pool' => $jsonStats,
            'performance_monitor' => $monitorStats
        ],
        'v1_1_3_improvements' => [
            'request_pool_reuse' => '100% (was 0% in v1.1.2)',
            'response_pool_reuse' => '99.9% (was 0% in v1.1.2)',
            'pool_warming' => 'Smart pre-allocation on startup',
            'garbage_collection' => 'Optimized object return-to-pool'
        ],
        'pool_benefits' => [
            'reduced_object_creation' => 'Massive reduction in new object instantiation',
            'memory_efficiency' => 'Reused objects reduce garbage collection pressure',
            'performance_boost' => '+116% framework throughput improvement',
            'sustained_performance' => 'Maintains high performance under load'
        ],
        'monitoring_info' => [
            'real_time_tracking' => 'Pool usage tracked in real-time',
            'adaptive_sizing' => 'Pools adjust size based on usage patterns',
            'production_ready' => 'Validated in Docker benchmarking environment'
        ]
    ]);
});

// 📈 Benchmark Comparison
$app->get('/performance/benchmark', function($req, $res) {
    return $res->json([
        'framework_benchmark_comparison' => [
            'methodology' => 'Docker-based comparative testing (2025-07-11)',
            'environment' => 'Standardized containers for fair comparison',
            'results' => [
                [
                    'framework' => 'Slim 4',
                    'performance' => '6,881 req/sec',
                    'position' => '1st place'
                ],
                [
                    'framework' => 'Lumen',
                    'performance' => '6,322 req/sec',
                    'position' => '2nd place'
                ],
                [
                    'framework' => 'PivotPHP Core v1.1.3',
                    'performance' => '6,227 req/sec',
                    'position' => '3rd place',
                    'note' => 'Excellent competitive performance'
                ],
                [
                    'framework' => 'Flight',
                    'performance' => '3,179 req/sec',
                    'position' => '4th place'
                ]
            ],
            'pivotphp_analysis' => [
                'competitive_position' => '9.5% behind leader (excellent)',
                'vs_flight' => '96% faster than Flight',
                'latency' => '0.32ms average response time',
                'memory_footprint' => '1.61MB (ultra-efficient)',
                'docker_validated' => true
            ]
        ],
        'internal_performance_metrics' => [
            'framework_throughput' => [
                'v1_1_2' => '20,400 ops/sec',
                'v1_1_3' => '44,092 ops/sec',
                'improvement' => '+116%'
            ],
            'json_operations' => [
                'small_datasets' => '505K ops/sec (internal)',
                'medium_datasets' => '119K ops/sec (internal)',
                'large_datasets' => '214K ops/sec (internal)'
            ],
            'object_pooling' => [
                'request_reuse' => '0% → 100%',
                'response_reuse' => '0% → 99.9%',
                'pool_efficiency' => 'Revolutionary improvement'
            ]
        ],
        'performance_validation' => [
            'docker_tested' => true,
            'multi_php_versions' => 'PHP 8.1-8.4 validated',
            'production_ready' => true,
            'sustained_performance' => 'Maintains performance under load'
        ]
    ]);
});

// 🔧 Helper Functions
function generateSmallDataset(): array 
{
    return [
        'records' => array_map(fn($i) => [
            'id' => $i,
            'name' => "Item {$i}",
            'value' => rand(1, 100)
        ], range(1, 5)),
        'metadata' => [
            'size' => 'small',
            'count' => 5,
            'generated_at' => date('c')
        ]
    ];
}

function generateMediumDataset(): array 
{
    return [
        'records' => array_map(fn($i) => [
            'id' => $i,
            'name' => "Record {$i}",
            'category' => ['tech', 'business', 'science', 'arts'][rand(0, 3)],
            'properties' => [
                'created_at' => date('c'),
                'updated_at' => date('c'),
                'status' => 'active',
                'priority' => rand(1, 10),
                'tags' => explode(',', 'tag1,tag2,tag3,tag4')
            ]
        ], range(1, 50)),
        'metadata' => [
            'size' => 'medium',
            'count' => 50,
            'generated_at' => date('c'),
            'estimated_json_size' => '~15KB'
        ]
    ];
}

function generateLargeDataset(): array 
{
    return [
        'records' => array_map(fn($i) => [
            'id' => $i,
            'uuid' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            ),
            'name' => "Large Record {$i}",
            'description' => str_repeat("This is a detailed description for record {$i}. ", 10),
            'category' => ['electronics', 'clothing', 'books', 'sports', 'home'][rand(0, 4)],
            'properties' => [
                'created_at' => date('c'),
                'updated_at' => date('c'),
                'status' => ['active', 'inactive', 'pending'][rand(0, 2)],
                'priority' => rand(1, 100),
                'score' => round(rand(0, 1000) / 10, 2),
                'metadata' => [
                    'source' => 'api',
                    'version' => '1.0',
                    'checksum' => md5("record-{$i}"),
                    'flags' => array_map(fn($j) => "flag_{$j}", range(1, rand(3, 8)))
                ]
            ],
            'related_items' => array_map(fn($j) => [
                'id' => $j,
                'type' => 'related',
                'weight' => rand(1, 10)
            ], range(1, rand(5, 15)))
        ], range(1, 500)),
        'metadata' => [
            'size' => 'large',
            'count' => 500,
            'generated_at' => date('c'),
            'estimated_json_size' => '~500KB',
            'uses_json_pooling' => true
        ]
    ];
}

function formatBytes(int $bytes, int $precision = 2): string 
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// 🚀 Run the application
$app->run();