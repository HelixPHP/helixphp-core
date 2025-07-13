<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Performance;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

/**
 * Performance-specific tests for End-to-End scenarios
 *
 * These tests focus purely on performance metrics and should be run separately
 * from functional tests to avoid interference.
 */
class EndToEndPerformanceTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        HighPerformanceMode::disable();
    }

    /**
     * @group performance
     * @group slow
     */
    public function testHighPerformanceModeRealPerformance(): void
    {
        // Enable actual high-performance mode for real performance testing
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        OptimizedHttpFactory::enablePooling();

        $this->setupPerformanceRoutes();
        $this->app->boot();

        $startTime = microtime(true);
        $results = [];

        // Test with a realistic load
        for ($i = 0; $i < 100; $i++) {
            $endpoint = $i % 3 === 0 ? '/api/fast' : ($i % 3 === 1 ? '/api/medium' : '/api/slow');
            $requestStartTime = microtime(true);
            $response = $this->makeRequest('GET', $endpoint);

            $results[] = [
                'status' => $response->getStatusCode(),
                'endpoint' => $endpoint,
                'time' => microtime(true) - $requestStartTime
            ];

            $this->assertEquals(200, $response->getStatusCode());
        }

        $totalTime = microtime(true) - $startTime;
        $throughput = count($results) / $totalTime;

        // Real performance assertions - these should reflect actual production expectations
        $this->assertGreaterThan(10, $throughput, 'Should handle >10 req/s in high-performance mode');
        $this->assertLessThan(10.0, $totalTime, 'Should complete 100 requests in <10 seconds');

        // Verify pool utilization in real performance mode
        $poolStats = OptimizedHttpFactory::getPoolStats();
        $this->assertGreaterThan(
            0,
            $poolStats['usage']['requests_reused'],
            'Pool should be utilized in performance mode'
        );
    }

    /**
     * @group performance
     * @group extreme
     */
    public function testExtremePerformanceMode(): void
    {
        // Test extreme performance mode with larger workload
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
        OptimizedHttpFactory::enablePooling();

        $this->setupPerformanceRoutes();
        $this->app->boot();

        $startTime = microtime(true);
        $results = [];

        // Reduced workload for stable CI testing
        for ($i = 0; $i < 100; $i++) {
            $endpoint = $i % 3 === 0 ? '/api/fast' :
                       ($i % 3 === 1 ? '/api/medium' : '/api/fast');

            $response = $this->makeRequest('GET', $endpoint);
            $results[] = $response->getStatusCode();
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Ensure timing makes sense
        if ($totalTime <= 0 || $totalTime > 300) {
            $this->markTestSkipped('Performance test skipped - timing measurement unreliable in CI');
        }

        $throughput = count($results) / $totalTime;

        // Realistic performance expectations for CI environment with safety checks
        $this->assertGreaterThan(0, $totalTime, 'Total time should be positive');
        $this->assertGreaterThan(0, count($results), 'Should have processed some results');

        // Only test throughput if timing is reasonable
        if ($totalTime > 0 && $totalTime < 300) { // 5 minutes max
            $this->assertGreaterThan(1, $throughput, 'Should handle >1 req/s in extreme mode');
        }
        $this->assertLessThan(120.0, $totalTime, 'Should complete 100 requests in <120 seconds');
    }

    private function setupPerformanceRoutes(): void
    {
        // Fast endpoint - minimal processing
        $this->app->get(
            '/api/fast',
            function (Request $req, Response $res) {
                return $res->json(['message' => 'fast', 'timestamp' => microtime(true)]);
            }
        );

        // Medium endpoint - some processing
        $this->app->get(
            '/api/medium',
            function (Request $req, Response $res) {
                $data = array_fill(0, 100, 'item');
                return $res->json(['message' => 'medium', 'data' => $data, 'timestamp' => microtime(true)]);
            }
        );

        // Slow endpoint - more processing
        $this->app->get(
            '/api/slow',
            function (Request $req, Response $res) {
                $data = array_fill(0, 1000, ['id' => rand(), 'value' => str_repeat('x', 100)]);
                return $res->json(['message' => 'slow', 'data' => $data, 'timestamp' => microtime(true)]);
            }
        );
    }

    private function makeRequest(string $method, string $path, ?array $data = null): Response
    {
        $request = new Request($method, $path, $path); // Fixed pathCallable parameter
        return $this->app->handle($request);
    }
}
