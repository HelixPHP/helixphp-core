<?php

declare(strict_types=1);

/**
 * Run all Express PHP benchmarks
 * 
 * This script orchestrates all benchmark executions
 * and generates a comprehensive report
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "🚀 Express PHP Comprehensive Benchmark Suite\n";
echo "==========================================\n\n";

// Check environment
$isDocker = getenv('BENCHMARK_ENV') === 'docker';
echo "Environment: " . ($isDocker ? "Docker" : "Local") . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "OPcache: " . (function_exists('opcache_get_status') ? 'Enabled' : 'Disabled') . "\n\n";

// List of benchmarks to run
$benchmarks = [
    'SimpleBenchmark' => 'Basic framework operations',
    'ExpressPhpBenchmark' => 'Core Express PHP features',
    'DatabaseBenchmark' => 'Database operations with PDO',
    'PSRPerformanceBenchmark' => 'PSR-15 middleware performance',
    'EnhancedAdvancedOptimizationsBenchmark' => 'Advanced optimizations',
];

$results = [];
$startTime = microtime(true);

// Run each benchmark
foreach ($benchmarks as $class => $description) {
    echo "📌 Running: $description ($class)\n";
    echo str_repeat('-', 50) . "\n";
    
    $benchmarkFile = __DIR__ . "/$class.php";
    
    if (!file_exists($benchmarkFile)) {
        echo "⚠️  Skipping: File not found\n\n";
        continue;
    }
    
    try {
        // Run benchmark in isolated process for clean results
        $output = shell_exec("php $benchmarkFile 2>&1");
        
        if ($output === null) {
            throw new Exception("Failed to execute benchmark");
        }
        
        echo $output;
        
        // Extract results if JSON output exists
        if (preg_match('/Results saved to: (.+\.json)/', $output, $matches)) {
            $resultsFile = trim($matches[1]);
            if (file_exists($resultsFile)) {
                $results[$class] = json_decode(file_get_contents($resultsFile), true);
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Small delay between benchmarks
    usleep(100000); // 100ms
}

$totalTime = microtime(true) - $startTime;

// Generate comprehensive report
echo "\n📊 Comprehensive Benchmark Report\n";
echo "================================\n\n";

echo sprintf("Total execution time: %.2f seconds\n", $totalTime);
echo sprintf("Benchmarks completed: %d/%d\n\n", count($results), count($benchmarks));

// Save comprehensive results
$comprehensiveResults = [
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => [
        'is_docker' => $isDocker,
        'php_version' => PHP_VERSION,
        'os' => PHP_OS,
        'opcache' => function_exists('opcache_get_status'),
        'memory_limit' => ini_get('memory_limit'),
        'total_time' => $totalTime
    ],
    'benchmarks' => $results
];

$resultsDir = __DIR__ . '/results';
if (!is_dir($resultsDir)) {
    mkdir($resultsDir, 0755, true);
}

$filename = $resultsDir . '/comprehensive_benchmark_' . date('Y-m-d_H-i-s') . '.json';
file_put_contents($filename, json_encode($comprehensiveResults, JSON_PRETTY_PRINT));

echo "💾 Comprehensive results saved to: $filename\n\n";

// Display summary
if (!empty($results)) {
    echo "📈 Performance Summary\n";
    echo "--------------------\n";
    
    foreach ($results as $benchmark => $data) {
        if (isset($data['results'])) {
            echo "\n$benchmark:\n";
            
            // Find the fastest operation
            $fastest = null;
            $fastestOps = 0;
            
            foreach ($data['results'] as $operation => $stats) {
                if (isset($stats['ops_per_sec']) && $stats['ops_per_sec'] > $fastestOps) {
                    $fastest = $operation;
                    $fastestOps = $stats['ops_per_sec'];
                }
            }
            
            if ($fastest) {
                echo sprintf("  Fastest: %s (%.2f ops/sec)\n", $fastest, $fastestOps);
            }
        }
    }
}

echo "\n✅ All benchmarks completed!\n";

// If running in Docker, ensure results are visible
if ($isDocker) {
    echo "\n📁 Results are available in the mounted volume: /app/benchmarks/results/\n";
}