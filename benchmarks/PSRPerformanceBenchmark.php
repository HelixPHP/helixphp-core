<?php
/**
 * PSR-7/PSR-15 Performance Benchmark
 *
 * This benchmark compares performance between traditional and PSR-7/PSR-15 implementations
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Express\Http\Adapters\GlobalsToServerRequestAdapter;
use Express\Http\Request;
use Express\Http\Response;
use Express\Http\Psr7\Factory\ServerRequestFactory;
use Express\Http\Psr7\Factory\ResponseFactory;
use Express\Http\Psr15\Middleware\CorsMiddleware;
use Express\Utils\Utils;

// Add the correct import for RequestHandler and CorsMiddleware if they exist
use Express\Http\Psr15\RequestHandler;

class PSRPerformanceBenchmark
{
    private int $iterations = 1000;
    private array $results = [];

    public function run(): void
    {
        echo "🚀 PSR-7/PSR-15 Performance Impact Analysis\n";
        echo "==========================================\n";
        echo "Iterations per test: " . number_format($this->iterations) . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n\n";

        // Traditional vs PSR-7 Request Creation
        $this->benchmarkRequestCreation();

        // Traditional vs PSR-7 Response Creation
        $this->benchmarkResponseCreation();

        // Middleware Performance Comparison
        $this->benchmarkMiddleware();

        // Header Manipulation Performance
        $this->benchmarkHeaderManipulation();

        // Memory Usage Analysis
        $this->benchmarkMemoryUsage();

        $this->displayResults();
        $this->saveResults();
    }

    private function benchmarkRequestCreation(): void
    {
        echo "🔄 Testing Request Creation Performance...\n";

        // Traditional Request Creation
        $traditional = $this->benchmark('Traditional Request', function() {
            $req = new Request('GET', '/api/test', '/api/test');
            return $req;
        });

        // PSR-7 Request Creation
        $psr7 = $this->benchmark('PSR-7 ServerRequest', function() {
            $factory = new ServerRequestFactory();
            $req = $factory->createServerRequest('GET', '/api/test', [
                'HTTP_HOST' => 'localhost'
            ]);
            return $req;
        });

        // PSR-7 From Globals
        $globals = $this->benchmark('PSR-7 From Globals', function() {
            $req = GlobalsToServerRequestAdapter::fromGlobals([
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/api/test',
                'HTTP_HOST' => 'localhost'
            ], [], [], []);
            return $req;
        });

        $this->results['request_creation'] = [
            'traditional' => $traditional,
            'psr7_factory' => $psr7,
            'psr7_globals' => $globals
        ];
    }

    private function benchmarkResponseCreation(): void
    {
        echo "🔄 Testing Response Creation Performance...\n";

        // Traditional Response Creation
        $traditional = $this->benchmark('Traditional Response', function() {
            $res = new Response();
            $res->json(['message' => 'Hello World']);
            return $res;
        });

        // PSR-7 Response Creation
        $psr7 = $this->benchmark('PSR-7 Response', function() {
            $factory = new ResponseFactory();
            $res = $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json');
            return $res;
        });

        $this->results['response_creation'] = [
            'traditional' => $traditional,
            'psr7' => $psr7
        ];
    }

    private function benchmarkMiddleware(): void
    {
        echo "🔄 Testing Middleware Performance...\n";

        // PSR-15 Middleware Stack
        $psr15 = $this->benchmark('PSR-15 Middleware Stack', function() {
            $handler = new RequestHandler();
            $handler->add(new CorsMiddleware());

            $factory = new ServerRequestFactory();
            $request = $factory->createServerRequest('GET', '/api/test');

            $response = $handler->handle($request);
            return $response;
        });

        $this->results['middleware'] = [
            'psr15_stack' => $psr15
        ];
    }

    private function benchmarkHeaderManipulation(): void
    {
        echo "🔄 Testing Header Manipulation Performance...\n";

        // Traditional Header Manipulation
        $traditional = $this->benchmark('Traditional Headers', function() {
            $res = new Response();
            $res->header('Content-Type', 'application/json');
            $res->header('X-Powered-By', 'Express PHP');
            $res->header('Cache-Control', 'no-cache');
            return $res;
        });

        // PSR-7 Header Manipulation
        $psr7 = $this->benchmark('PSR-7 Headers', function() {
            $factory = new ResponseFactory();
            $res = $factory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-Powered-By', 'Express PHP')
                ->withHeader('Cache-Control', 'no-cache');
            return $res;
        });

        $this->results['header_manipulation'] = [
            'traditional' => $traditional,
            'psr7' => $psr7
        ];
    }

    private function benchmarkMemoryUsage(): void
    {
        echo "🔄 Testing Memory Usage...\n";

        $memoryBefore = memory_get_usage();

        // Create traditional objects
        $traditionalObjects = [];
        for ($i = 0; $i < 100; $i++) {
            $req = new Request('GET', '/', '/');
            $res = new Response();
            $traditionalObjects[] = [$req, $res];
        }

        $memoryTraditional = memory_get_usage() - $memoryBefore;
        unset($traditionalObjects);

        $memoryBefore = memory_get_usage();

        // Create PSR-7 objects
        $psr7Objects = [];
        $reqFactory = new ServerRequestFactory();
        $resFactory = new ResponseFactory();

        for ($i = 0; $i < 100; $i++) {
            $req = $reqFactory->createServerRequest('GET', '/test');
            $res = $resFactory->createResponse(200);
            $psr7Objects[] = [$req, $res];
        }

        $memoryPSR7 = memory_get_usage() - $memoryBefore;
        unset($psr7Objects);

        $this->results['memory_usage'] = [
            'traditional' => $memoryTraditional,
            'psr7' => $memoryPSR7,
            'difference' => $memoryPSR7 - $memoryTraditional,
            'difference_percent' => (($memoryPSR7 - $memoryTraditional) / $memoryTraditional) * 100
        ];
    }

    private function benchmark(string $name, callable $callback): array
    {
        echo "   - $name... ";

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        for ($i = 0; $i < $this->iterations; $i++) {
            $callback();
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $totalTime = $endTime - $startTime;
        $avgTime = $totalTime / $this->iterations;
        $opsPerSecond = 1 / $avgTime;
        $memoryUsed = $endMemory - $startMemory;

        echo "✅ " . number_format($opsPerSecond, 0) . " ops/sec\n";

        return [
            'ops_per_second' => $opsPerSecond,
            'avg_time_microseconds' => $avgTime * 1000000,
            'total_time' => $totalTime,
            'memory_used' => $memoryUsed
        ];
    }

    private function displayResults(): void
    {
        echo "\n📊 PSR-7/PSR-15 PERFORMANCE ANALYSIS\n";
        echo "=====================================\n\n";

        // Request Creation Comparison
        echo "📈 Request Creation Performance:\n";
        $traditional = $this->results['request_creation']['traditional'];
        $psr7Factory = $this->results['request_creation']['psr7_factory'];
        $psr7Globals = $this->results['request_creation']['psr7_globals'];

        echo sprintf("   Traditional:     %s ops/sec (%.2f μs)\n",
            number_format($traditional['ops_per_second']),
            $traditional['avg_time_microseconds']);
        echo sprintf("   PSR-7 Factory:   %s ops/sec (%.2f μs)\n",
            number_format($psr7Factory['ops_per_second']),
            $psr7Factory['avg_time_microseconds']);
        echo sprintf("   PSR-7 Globals:   %s ops/sec (%.2f μs)\n",
            number_format($psr7Globals['ops_per_second']),
            $psr7Globals['avg_time_microseconds']);

        $impactFactory = (($traditional['ops_per_second'] - $psr7Factory['ops_per_second']) / $traditional['ops_per_second']) * 100;
        $impactGlobals = (($traditional['ops_per_second'] - $psr7Globals['ops_per_second']) / $traditional['ops_per_second']) * 100;

        echo sprintf("   Impact Factory: %.1f%% %s\n", abs($impactFactory), $impactFactory > 0 ? 'slower' : 'faster');
        echo sprintf("   Impact Globals: %.1f%% %s\n\n", abs($impactGlobals), $impactGlobals > 0 ? 'slower' : 'faster');

        // Response Creation Comparison
        echo "📈 Response Creation Performance:\n";
        $traditional = $this->results['response_creation']['traditional'];
        $psr7 = $this->results['response_creation']['psr7'];

        echo sprintf("   Traditional:     %s ops/sec (%.2f μs)\n",
            number_format($traditional['ops_per_second']),
            $traditional['avg_time_microseconds']);
        echo sprintf("   PSR-7:           %s ops/sec (%.2f μs)\n",
            number_format($psr7['ops_per_second']),
            $psr7['avg_time_microseconds']);

        $impact = (($traditional['ops_per_second'] - $psr7['ops_per_second']) / $traditional['ops_per_second']) * 100;
        echo sprintf("   Impact: %.1f%% %s\n\n", abs($impact), $impact > 0 ? 'slower' : 'faster');

        // Header Manipulation Comparison
        echo "📈 Header Manipulation Performance:\n";
        $traditional = $this->results['header_manipulation']['traditional'];
        $psr7 = $this->results['header_manipulation']['psr7'];

        echo sprintf("   Traditional:     %s ops/sec (%.2f μs)\n",
            number_format($traditional['ops_per_second']),
            $traditional['avg_time_microseconds']);
        echo sprintf("   PSR-7:           %s ops/sec (%.2f μs)\n",
            number_format($psr7['ops_per_second']),
            $psr7['avg_time_microseconds']);

        $impact = (($traditional['ops_per_second'] - $psr7['ops_per_second']) / $traditional['ops_per_second']) * 100;
        echo sprintf("   Impact: %.1f%% %s\n\n", abs($impact), $impact > 0 ? 'slower' : 'faster');

        // Memory Usage
        echo "💾 Memory Usage Analysis:\n";
        $memory = $this->results['memory_usage'];
        echo sprintf("   Traditional (100 objects): %s\n", Utils::formatBytes($memory['traditional']));
        echo sprintf("   PSR-7 (100 objects):       %s\n", Utils::formatBytes($memory['psr7']));
        echo sprintf("   Difference:                 %s (%.1f%%)\n\n",
            Utils::formatBytes($memory['difference']),
            $memory['difference_percent']);
    }

    private function saveResults(): void
    {
        $reportFile = __DIR__ . '/reports/psr_performance_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($this->results, JSON_PRETTY_PRINT));
        echo "📋 Report saved to: $reportFile\n";
    }
}

// Execute benchmark
$benchmark = new PSRPerformanceBenchmark();
$benchmark->run();
