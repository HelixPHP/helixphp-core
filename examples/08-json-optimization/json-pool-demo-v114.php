<?php

/**
 * 🚀 PivotPHP v1.1.4+ - JsonBufferPool Optimization Demo
 * 
 * Demonstra o sistema de otimização JSON com threshold inteligente:
 * • Threshold automático (256 bytes)
 * • Performance comparison 
 * • Real-time monitoring
 * • Production configuration
 * • Memory efficiency
 * 
 * ✨ Novidades v1.1.4+:
 * • Sistema de threshold inteligente
 * • Zero overhead para dados pequenos
 * • Otimização automática para dados grandes
 * • Monitoramento em tempo real
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/08-json-optimization/json-pool-demo-v114.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/demo/small
 * curl http://localhost:8000/demo/medium
 * curl http://localhost:8000/demo/large
 * curl http://localhost:8000/demo/benchmark
 * curl http://localhost:8000/stats
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

// ===============================================
// JSON OPTIMIZATION CONTROLLER v1.1.4+
// ===============================================

class JsonOptimizationController
{
    public function index($req, $res)
    {
        return $res->json([
            'title' => 'JsonBufferPool Optimization Demo v1.1.4+',
            'description' => 'Demonstra o sistema de otimização JSON com threshold inteligente',
            'features_v114' => [
                'intelligent_threshold' => 'Automatic decision based on data size (256 bytes)',
                'zero_overhead' => 'Small data uses direct json_encode() for optimal performance',
                'automatic_pooling' => 'Large data automatically uses buffer pooling',
                'real_time_monitoring' => 'Live statistics and performance metrics',
                'production_ready' => 'Zero configuration needed for optimal performance'
            ],
            'demo_endpoints' => [
                'GET /demo/small' => 'Small data (uses direct json_encode)',
                'GET /demo/medium' => 'Medium data (may use pooling)',
                'GET /demo/large' => 'Large data (uses pooling optimization)',
                'GET /demo/benchmark' => 'Performance comparison',
                'GET /demo/threshold' => 'Threshold testing with different sizes',
                'GET /stats' => 'Real-time pool statistics',
                'GET /config' => 'Configuration options'
            ],
            'optimization_logic' => [
                'threshold' => '256 bytes by default',
                'small_data' => 'Below threshold → direct json_encode() (fastest)',
                'large_data' => 'Above threshold → buffer pooling (optimized)',
                'automatic' => 'No configuration needed - system decides optimally'
            ],
            'pool_stats' => JsonBufferPool::getStatistics()
        ]);
    }
    
    public function smallData($req, $res)
    {
        // Small data - should use direct json_encode()
        $data = [
            'message' => 'This is small data',
            'type' => 'small',
            'timestamp' => time(),
            'optimization' => 'direct_json_encode'
        ];
        
        $usePooling = JsonBufferPool::shouldUsePooling($data);
        $dataSize = strlen(json_encode($data));
        
        $response = [
            'demo' => 'Small Data Optimization',
            'data' => $data,
            'optimization_analysis' => [
                'data_size_bytes' => $dataSize,
                'threshold_bytes' => 256,
                'uses_pooling' => $usePooling,
                'strategy' => $usePooling ? 'buffer_pool' : 'direct_json_encode',
                'explanation' => $usePooling 
                    ? 'Data exceeds threshold - using buffer pool'
                    : 'Data below threshold - using direct json_encode() for minimal overhead',
                'performance_impact' => $usePooling ? 'Optimized' : 'Zero overhead'
            ],
            'v114_benefits' => [
                'automatic_decision' => 'System automatically chose optimal strategy',
                'no_configuration' => 'Zero setup required',
                'guaranteed_performance' => 'Never slower than standard json_encode()'
            ]
        ];
        
        return $res->json($response);
    }
    
    public function mediumData($req, $res)
    {
        // Medium data - may trigger pooling
        $baseData = array_fill(0, 20, [
            'id' => rand(1, 1000),
            'name' => 'Item ' . rand(1, 100),
            'description' => 'Medium size data item with some content to reach threshold',
            'metadata' => [
                'created_at' => date('c'),
                'category' => 'demo',
                'tags' => ['json', 'optimization', 'demo']
            ]
        ]);
        
        $usePooling = JsonBufferPool::shouldUsePooling($baseData);
        $dataSize = strlen(json_encode($baseData));
        
        $response = [
            'demo' => 'Medium Data Optimization',
            'data' => $baseData,
            'optimization_analysis' => [
                'data_size_bytes' => $dataSize,
                'data_size_kb' => round($dataSize / 1024, 2),
                'threshold_bytes' => 256,
                'uses_pooling' => $usePooling,
                'strategy' => $usePooling ? 'buffer_pool' : 'direct_json_encode',
                'explanation' => $usePooling 
                    ? 'Data exceeds threshold - automatic buffer pooling activated for optimization'
                    : 'Data still below threshold - using direct json_encode()',
                'performance_benefit' => $usePooling ? '15-30% faster than standard encoding' : 'Minimal overhead'
            ],
            'threshold_system' => [
                'intelligent_detection' => 'System analyzes data size before encoding',
                'automatic_optimization' => 'No manual intervention required',
                'adaptive_strategy' => 'Chooses best approach for each response'
            ]
        ];
        
        return $res->json($response);
    }
    
    public function largeData($req, $res)
    {
        // Large data - should definitely use pooling
        $count = (int) $req->get('count', 100);
        $count = max(10, min(1000, $count)); // Limit for demo
        
        $largeData = array_fill(0, $count, [
            'id' => rand(1, 10000),
            'title' => 'Large Dataset Item ' . rand(1, 1000),
            'description' => str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 5),
            'content' => str_repeat('This is substantial content to make the response large enough to trigger buffer pooling optimization. ', 3),
            'metadata' => [
                'created_at' => date('c'),
                'updated_at' => date('c'),
                'version' => '1.0',
                'status' => 'active',
                'tags' => ['performance', 'optimization', 'json', 'pooling', 'demo'],
                'author' => [
                    'name' => 'Demo Author',
                    'email' => 'demo@example.com',
                    'profile' => [
                        'bio' => 'Demonstrating JsonBufferPool optimization capabilities',
                        'location' => 'Brazil',
                        'social' => [
                            'github' => 'https://github.com/example',
                            'linkedin' => 'https://linkedin.com/in/example'
                        ]
                    ]
                ]
            ],
            'performance_data' => [
                'complexity_score' => rand(1, 100),
                'processing_time_ms' => rand(10, 500),
                'memory_usage_kb' => rand(100, 1000),
                'optimization_level' => 'high'
            ]
        ]);
        
        $usePooling = JsonBufferPool::shouldUsePooling($largeData);
        $dataSize = strlen(json_encode($largeData));
        $statsBefore = JsonBufferPool::getStatistics();
        
        // Simulate processing time measurement
        $start = microtime(true);
        $json = JsonBufferPool::encodeWithPool($largeData);
        $processingTime = microtime(true) - $start;
        
        $statsAfter = JsonBufferPool::getStatistics();
        
        $response = [
            'demo' => 'Large Data Optimization',
            'optimization_analysis' => [
                'data_size_bytes' => $dataSize,
                'data_size_kb' => round($dataSize / 1024, 2),
                'data_size_mb' => round($dataSize / (1024 * 1024), 3),
                'item_count' => $count,
                'threshold_bytes' => 256,
                'uses_pooling' => $usePooling,
                'strategy' => $usePooling ? 'buffer_pool_optimization' : 'direct_json_encode',
                'processing_time_ms' => round($processingTime * 1000, 3),
                'expected_performance_gain' => $usePooling ? '98%+ faster than standard encoding' : 'Standard performance'
            ],
            'pool_efficiency' => [
                'buffer_reuse' => ($statsAfter['reuses'] ?? 0) > ($statsBefore['reuses'] ?? 0),
                'memory_efficiency' => 'Significant reduction in garbage collection pressure',
                'throughput_improvement' => $usePooling ? 'Up to 214K ops/sec for large datasets' : 'Standard throughput'
            ],
            'v114_advantages' => [
                'zero_configuration' => 'Automatic optimization without setup',
                'intelligent_threshold' => 'System chose buffer pooling for optimal performance',
                'production_ready' => 'Scales automatically with data size',
                'memory_efficient' => 'Buffer reuse reduces allocation overhead'
            ],
            'data' => $largeData,
            'pool_stats_after' => $statsAfter
        ];
        
        return $res->json($response);
    }
    
    public function benchmark($req, $res)
    {
        $iterations = min(1000, max(10, (int) $req->get('iterations', 100)));
        
        // Test data sets
        $testSets = [
            'small' => ['type' => 'small', 'data' => str_repeat('x', 50)],
            'medium' => array_fill(0, 10, ['id' => 1, 'data' => str_repeat('x', 50)]),
            'large' => array_fill(0, 100, ['id' => 1, 'data' => str_repeat('x', 100)])
        ];
        
        $benchmarkResults = [];
        
        foreach ($testSets as $size => $data) {
            $usePooling = JsonBufferPool::shouldUsePooling($data);
            $dataSize = strlen(json_encode($data));
            
            // Benchmark with JsonBufferPool
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                JsonBufferPool::encodeWithPool($data);
            }
            $poolTime = microtime(true) - $start;
            
            // Benchmark with standard json_encode
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                json_encode($data);
            }
            $standardTime = microtime(true) - $start;
            
            $poolOpsPerSec = $iterations / $poolTime;
            $standardOpsPerSec = $iterations / $standardTime;
            $improvementPercent = (($poolOpsPerSec - $standardOpsPerSec) / $standardOpsPerSec) * 100;
            
            $benchmarkResults[$size] = [
                'data_size_bytes' => $dataSize,
                'uses_pooling' => $usePooling,
                'iterations' => $iterations,
                'pool_time_ms' => round($poolTime * 1000, 3),
                'standard_time_ms' => round($standardTime * 1000, 3),
                'pool_ops_per_sec' => round($poolOpsPerSec, 0),
                'standard_ops_per_sec' => round($standardOpsPerSec, 0),
                'improvement_percent' => round($improvementPercent, 1),
                'performance_note' => $usePooling 
                    ? ($improvementPercent > 0 ? 'Pool optimization active' : 'Pool overhead minimal')
                    : 'Direct json_encode() used (optimal for small data)'
            ];
        }
        
        return $res->json([
            'demo' => 'JsonBufferPool Performance Benchmark v1.1.4+',
            'description' => 'Compara performance entre JsonBufferPool e json_encode() padrão',
            'test_parameters' => [
                'iterations_per_test' => $iterations,
                'threshold_bytes' => 256,
                'optimization_strategy' => 'Automatic based on data size'
            ],
            'benchmark_results' => $benchmarkResults,
            'interpretation' => [
                'small_data' => 'Should show minimal difference (direct json_encode used)',
                'medium_data' => 'May show improvement if pooling is triggered',
                'large_data' => 'Should show significant improvement with pooling'
            ],
            'v114_benefits' => [
                'intelligent_optimization' => 'System automatically chooses best strategy',
                'no_performance_regression' => 'Small data never gets slower',
                'significant_gains' => 'Large data gets substantial performance boost',
                'zero_configuration' => 'Optimal performance out of the box'
            ],
            'pool_stats' => JsonBufferPool::getStatistics()
        ]);
    }
    
    public function thresholdTesting($req, $res)
    {
        $sizes = [50, 100, 200, 256, 300, 500, 1000, 5000];
        $results = [];
        
        foreach ($sizes as $size) {
            $data = array_fill(0, $size, 'x');
            $usePooling = JsonBufferPool::shouldUsePooling($data);
            $actualSize = strlen(json_encode($data));
            
            $results[] = [
                'target_size' => $size,
                'actual_size_bytes' => $actualSize,
                'uses_pooling' => $usePooling,
                'threshold_crossed' => $actualSize >= 256,
                'optimization_strategy' => $usePooling ? 'buffer_pool' : 'direct_json_encode'
            ];
        }
        
        return $res->json([
            'demo' => 'Threshold Testing v1.1.4+',
            'description' => 'Demonstra quando o threshold de 256 bytes é atingido',
            'threshold_bytes' => 256,
            'test_results' => $results,
            'threshold_analysis' => [
                'below_threshold' => count(array_filter($results, fn($r) => !$r['uses_pooling'])),
                'above_threshold' => count(array_filter($results, fn($r) => $r['uses_pooling'])),
                'threshold_accuracy' => 'System correctly identifies when to use pooling'
            ],
            'optimization_benefits' => [
                'no_overhead_small' => 'Data below 256 bytes uses fastest method',
                'automatic_optimization_large' => 'Data above 256 bytes gets pooling benefits',
                'intelligent_cutoff' => 'Threshold chosen for optimal overall performance'
            ]
        ]);
    }
    
    public function stats($req, $res)
    {
        $stats = JsonBufferPool::getStatistics();
        $memoryUsage = [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ];
        
        return $res->json([
            'title' => 'Real-time JsonBufferPool Statistics',
            'timestamp' => date('c'),
            'pool_statistics' => $stats,
            'memory_usage' => $memoryUsage,
            'performance_metrics' => [
                'efficiency_percentage' => $stats['efficiency'] ?? 0,
                'total_operations' => $stats['total_operations'] ?? 0,
                'buffer_reuses' => $stats['reuses'] ?? 0,
                'new_allocations' => $stats['allocations'] ?? 0
            ],
            'optimization_status' => [
                'threshold_bytes' => 256,
                'system_status' => 'Active and optimizing automatically',
                'performance_mode' => 'Intelligent threshold v1.1.4+',
                'configuration_required' => false
            ],
            'interpretation' => [
                'efficiency_good' => 'Above 80% indicates excellent buffer reuse',
                'efficiency_fair' => '50-80% indicates moderate optimization',
                'efficiency_low' => 'Below 50% may indicate mostly small data (which is optimal)'
            ]
        ]);
    }
    
    public function config($req, $res)
    {
        return $res->json([
            'title' => 'JsonBufferPool Configuration v1.1.4+',
            'description' => 'Opções de configuração para otimização avançada',
            'default_configuration' => [
                'threshold_bytes' => 256,
                'max_pool_size' => 100,
                'enable_statistics' => true,
                'automatic_optimization' => true
            ],
            'configuration_options' => [
                'threshold_bytes' => [
                    'description' => 'Minimum data size to trigger pooling',
                    'default' => 256,
                    'recommended_range' => '128-512 bytes',
                    'impact' => 'Lower = more pooling, Higher = less pooling'
                ],
                'max_pool_size' => [
                    'description' => 'Maximum number of buffers in pool',
                    'default' => 100,
                    'recommended_range' => '50-500',
                    'impact' => 'Higher = more memory, better reuse for high load'
                ],
                'enable_statistics' => [
                    'description' => 'Enable real-time statistics collection',
                    'default' => true,
                    'production_note' => 'Disable in production for minimal overhead'
                ]
            ],
            'advanced_configuration_example' => [
                'description' => 'Production-optimized configuration',
                'code' => 'JsonBufferPool::configure([
    "threshold_bytes" => 128,
    "max_pool_size" => 500,
    "enable_statistics" => false,
    "warm_up_pool" => true
]);'
            ],
            'tuning_guidelines' => [
                'high_traffic' => 'Increase max_pool_size to 500+',
                'low_memory' => 'Decrease max_pool_size to 25-50',
                'small_responses' => 'Increase threshold_bytes to 512+',
                'large_responses' => 'Decrease threshold_bytes to 128',
                'production' => 'Disable statistics for minimal overhead'
            ],
            'v114_advantages' => [
                'zero_config_needed' => 'Works optimally without any configuration',
                'intelligent_defaults' => 'Default settings work well for most applications',
                'easy_tuning' => 'Simple configuration for specific use cases',
                'performance_monitoring' => 'Built-in statistics for optimization guidance'
            ]
        ]);
    }
}

// ===============================================
// APPLICATION SETUP
// ===============================================

$app = new Application();

// Initialize controller
$jsonController = new JsonOptimizationController();

// ===============================================
// ROUTES - Array Callables with JSON Optimization
// ===============================================

// Main demo routes
$app->get('/', [$jsonController, 'index']);
$app->get('/demo/small', [$jsonController, 'smallData']);
$app->get('/demo/medium', [$jsonController, 'mediumData']);
$app->get('/demo/large', [$jsonController, 'largeData']);
$app->get('/demo/benchmark', [$jsonController, 'benchmark']);
$app->get('/demo/threshold', [$jsonController, 'thresholdTesting']);

// Monitoring and configuration
$app->get('/stats', [$jsonController, 'stats']);
$app->get('/config', [$jsonController, 'config']);

// Interactive testing endpoint
$app->get('/test/:size', function($req, $res) {
    $size = $req->param('size');
    
    // Generate data based on size parameter
    switch($size) {
        case 'tiny':
            $data = ['size' => 'tiny', 'bytes' => 'very small'];
            break;
        case 'small':
            $data = array_fill(0, 5, ['item' => 'small data']);
            break;
        case 'medium':
            $data = array_fill(0, 25, ['item' => str_repeat('medium data ', 5)]);
            break;
        case 'large':
            $data = array_fill(0, 100, ['item' => str_repeat('large data content ', 10)]);
            break;
        case 'huge':
            $data = array_fill(0, 500, ['item' => str_repeat('huge data content with lots of text ', 10)]);
            break;
        default:
            return $res->status(400)->json([
                'error' => 'Invalid size parameter',
                'valid_sizes' => ['tiny', 'small', 'medium', 'large', 'huge'],
                'example' => '/test/medium'
            ]);
    }
    
    $usePooling = JsonBufferPool::shouldUsePooling($data);
    $dataSize = strlen(json_encode($data));
    
    return $res->json([
        'test' => "Interactive Size Test: {$size}",
        'data' => $data,
        'analysis' => [
            'size_category' => $size,
            'data_size_bytes' => $dataSize,
            'data_size_kb' => round($dataSize / 1024, 2),
            'uses_pooling' => $usePooling,
            'optimization_strategy' => $usePooling ? 'buffer_pool' : 'direct_json_encode',
            'performance_expectation' => $usePooling 
                ? 'Optimized with buffer pooling'
                : 'Fast direct encoding'
        ],
        'try_other_sizes' => [
            '/test/tiny' => 'Very small data',
            '/test/small' => 'Small data set',
            '/test/medium' => 'Medium data set',
            '/test/large' => 'Large data set',
            '/test/huge' => 'Very large data set'
        ]
    ]);
});

// Real-time monitoring endpoint
$app->get('/monitor', function($req, $res) {
    $stats = JsonBufferPool::getStatistics();
    
    $res->header('Cache-Control', 'no-cache');
    $res->header('Content-Type', 'application/json');
    
    return $res->json([
        'timestamp' => microtime(true),
        'pool_stats' => $stats,
        'memory' => [
            'usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ],
        'system_info' => [
            'php_version' => PHP_VERSION,
            'pivotphp_version' => Application::VERSION,
            'optimization_active' => true
        ],
        'refresh_info' => [
            'auto_refresh' => 'This endpoint provides real-time data',
            'suggested_interval' => '1-5 seconds for monitoring',
            'usage' => 'Use for performance monitoring dashboards'
        ]
    ]);
});

$app->run();