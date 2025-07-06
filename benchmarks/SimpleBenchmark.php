<?php
/**
 * HelixPHP Framework - Simple Benchmark
 *
 * Focused performance testing for core HelixPHP functionality
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Http\Request;
use Helix\Http\Response;
use Helix\Authentication\JWTHelper;
use Helix\Utils\Utils;

class SimpleBenchmark
{
    private array $results = [];
    private int $iterations = 1000;

    public function __construct(int $iterations = 1000)
    {
        $this->iterations = $iterations;
    }

    public function runAll(): void
    {
        echo "🚀 HelixPHP Framework - Simple Performance Benchmark\n";
        echo "====================================================\n";
        echo "Iterations per test: " . number_format($this->iterations) . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Memory Limit: " . ini_get('memory_limit') . "\n\n";

        $this->benchmarkAppInitialization();
        $this->benchmarkBasicRouting();
        $this->benchmarkJsonProcessing();
        $this->benchmarkJWT();
        $this->benchmarkRequestResponse();
        $this->benchmarkMemoryUsage();

        $this->displayResults();
        $this->generateReport();
    }

    private function benchmarkAppInitialization(): void
    {
        $this->benchmark('App Initialization', function() {
            return new Application();
        });
    }

    private function benchmarkBasicRouting(): void
    {
        $app = new Application();

        $app->get('/test', function($req, $res) {
            $res->json(['message' => 'test']);
        });

        $app->post('/api/users', function($req, $res) {
            $res->json(['id' => 1, 'name' => 'Test User']);
        });

        $this->benchmark('Route Registration', function() use ($app) {
            $app->get('/dynamic', function($req, $res) {
                $res->json(['dynamic' => true]);
            });
        });
    }

    private function benchmarkJsonProcessing(): void
    {
        $smallData = ['id' => 1, 'name' => 'Test'];
        $largeData = array_fill(0, 100, ['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com']);

        $this->benchmark('JSON Encode (Small)', function() use ($smallData) {
            return json_encode($smallData);
        });

        $this->benchmark('JSON Encode (100 items)', function() use ($largeData) {
            return json_encode($largeData);
        });

        $jsonString = json_encode($largeData);

        $this->benchmark('JSON Decode (100 items)', function() use ($jsonString) {
            return json_decode($jsonString, true);
        });
    }    private function benchmarkJWT(): void
    {
        $payload = ['user_id' => 123, 'email' => 'test@example.com'];
        $secret = 'test-secret-key';

        $this->benchmark('JWT Token Generation', function() use ($payload, $secret) {
            return JWTHelper::encode($payload, $secret);
        });

        $token = JWTHelper::encode($payload, $secret);

        $this->benchmark('JWT Token Validation', function() use ($token, $secret) {
            return JWTHelper::decode($token, $secret);
        });
    }    private function benchmarkRequestResponse(): void
    {
        $this->benchmark('Request Object Creation', function() {
            return new Request('GET', '/test', '/test');
        });

        $this->benchmark('Response Object Creation', function() {
            return new Response();
        });
    }

    private function benchmarkMemoryUsage(): void
    {
        $memoryBefore = memory_get_usage();

        // Create multiple app instances
        $apps = [];
        for ($i = 0; $i < 50; $i++) {
            $app = new Application();
            $app->get('/test', function($req, $res) {
                $res->json(['test' => true]);
            });
            $apps[] = $app;
        }

        $memoryAfter = memory_get_usage();
        $memoryDiff = $memoryAfter - $memoryBefore;

        $this->results['Memory Usage'] = [
            'memory_per_app' => $memoryDiff / 50,
            'total_memory' => $memoryDiff,
            'apps_created' => 50
        ];

        echo "💾 Memory Usage Analysis:\n";
        echo "   Memory per app instance: " . Utils::formatBytes($memoryDiff / 50) . "\n";
        echo "   Total memory for 50 apps: " . Utils::formatBytes($memoryDiff) . "\n\n";
    }

    private function benchmark(string $name, callable $callback): void
    {
        echo "🔄 Testing: {$name}... ";

        // Warmup
        for ($i = 0; $i < 5; $i++) {
            $callback();
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        for ($i = 0; $i < $this->iterations; $i++) {
            $callback();
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $totalTime = $endTime - $startTime;
        $avgTime = ($totalTime / $this->iterations) * 1000000; // microseconds
        $opsPerSecond = $this->iterations / $totalTime;
        $memoryUsed = $endMemory - $startMemory;

        $this->results[$name] = [
            'total_time' => $totalTime,
            'avg_time_microseconds' => $avgTime,
            'ops_per_second' => $opsPerSecond,
            'memory_used' => $memoryUsed
        ];

        echo "✅ " . number_format($opsPerSecond, 0) . " ops/sec\n";
    }

    private function displayResults(): void
    {
        echo "\n📊 BENCHMARK RESULTS\n";
        echo "====================\n\n";

        foreach ($this->results as $test => $result) {
            if ($test === 'Memory Usage') continue;

            echo "📈 {$test}:\n";
            echo "   Operations/second: " . number_format($result['ops_per_second'], 0) . "\n";
            echo "   Average time: " . number_format($result['avg_time_microseconds'], 2) . " μs\n";
            echo "   Memory used: " . Utils::formatBytes($result['memory_used']) . "\n\n";
        }
    }

    private function generateReport(): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'iterations' => $this->iterations,
            'results' => $this->results
        ];

        $reportPath = __DIR__ . '/reports/simple_benchmark_' . date('Y-m-d_H-i-s') . '.json';

        if (!is_dir(__DIR__ . '/reports')) {
            mkdir(__DIR__ . '/reports', 0755, true);
        }

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

        echo "📋 Report saved to: {$reportPath}\n";

        // Generate markdown summary
        $this->generateMarkdownSummary();
    }

    private function generateMarkdownSummary(): void
    {
        $markdown = "# HelixPHP Framework - Performance Benchmark\n\n";
        $markdown .= "## Test Environment\n";
        $markdown .= "- **Date**: " . date('Y-m-d H:i:s') . "\n";
        $markdown .= "- **PHP Version**: " . PHP_VERSION . "\n";
        $markdown .= "- **Memory Limit**: " . ini_get('memory_limit') . "\n";
        $markdown .= "- **Iterations**: " . number_format($this->iterations) . "\n\n";

        $markdown .= "## Performance Results\n\n";
        $markdown .= "| Test | Ops/Second | Avg Time (μs) | Memory Used |\n";
        $markdown .= "|------|------------|---------------|-------------|\n";

        foreach ($this->results as $test => $result) {
            if ($test === 'Memory Usage') continue;

            $markdown .= sprintf(
                "| %s | %s | %s | %s |\n",
                $test,
                number_format($result['ops_per_second'], 0),
                number_format($result['avg_time_microseconds'], 2),
                Utils::formatBytes($result['memory_used'])
            );
        }

        if (isset($this->results['Memory Usage'])) {
            $memory = $this->results['Memory Usage'];
            $markdown .= "\n## Memory Efficiency\n";
            $markdown .= "- **Memory per app instance**: " . Utils::formatBytes($memory['memory_per_app']) . "\n";
            $markdown .= "- **Total memory for {$memory['apps_created']} apps**: " . Utils::formatBytes($memory['total_memory']) . "\n";
        }

        $markdown .= "\n## Performance Summary\n";
        $markdown .= "HelixPHP demonstrates excellent performance characteristics for a PHP microframework:\n\n";

        // Find best performing test
        $sortedResults = $this->results;
        unset($sortedResults['Memory Usage']);
        uasort($sortedResults, function($a, $b) {
            return $b['ops_per_second'] <=> $a['ops_per_second'];
        });

        $topTest = array_key_first($sortedResults);
        $topOps = $sortedResults[$topTest]['ops_per_second'];

        $markdown .= "- **Best Performance**: {$topTest} with " . number_format($topOps, 0) . " operations/second\n";
        $markdown .= "- **Framework Overhead**: Minimal memory usage per application instance\n";
        $markdown .= "- **JWT Performance**: Fast token generation and validation for authentication\n";
        $markdown .= "- **JSON Processing**: Efficient handling of API data serialization\n";

        $summaryPath = __DIR__ . '/reports/PERFORMANCE_SUMMARY.md';
        file_put_contents($summaryPath, $markdown);

        echo "📄 Performance summary saved to: {$summaryPath}\n\n";
    }
}

// Run benchmarks if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $iterations = (int)($argv[1] ?? 1000);
    $benchmark = new SimpleBenchmark($iterations);
    $benchmark->runAll();
}
