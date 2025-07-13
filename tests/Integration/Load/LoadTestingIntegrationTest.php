<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Load;

use PivotPHP\Core\Tests\Integration\IntegrationTestCase;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Load Testing Integration Tests
 *
 * Tests the framework's behavior under various load conditions:
 * - Concurrent request simulation and handling
 * - Memory management under stress
 * - Performance degradation patterns
 * - Throughput and latency measurements
 * - Resource exhaustion scenarios
 * - Recovery and stability testing
 * - System limits and breaking points
 *
 * @group integration
 * @group load
 * @group stress
 */
class LoadTestingIntegrationTest extends IntegrationTestCase
{
    private array $loadMetrics = [];
    private int $maxConcurrentRequests = 50;
    private int $stressTestDuration = 10; // seconds

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupLoadTestRoutes();
        $this->resetLoadMetrics();
    }

    /**
     * Setup routes for load testing
     */
    private function setupLoadTestRoutes(): void
    {
        // Simple endpoint for basic load testing
        $this->app->get(
            '/load/simple',
            function ($req, $res) {
                return $res->json(
                    [
                        'timestamp' => microtime(true),
                        'memory' => memory_get_usage(true),
                        'message' => 'Simple load test endpoint'
                    ]
                );
            }
        );

        // CPU intensive endpoint
        $this->app->get(
            '/load/cpu-intensive',
            function ($req, $res) {
                $start = microtime(true);

            // Simulate CPU-intensive work
                $result = 0;
                for ($i = 0; $i < 100000; $i++) {
                    $result += sqrt($i) * sin($i);
                }

                $duration = (microtime(true) - $start) * 1000;

                return $res->json(
                    [
                        'computation_result' => $result,
                        'processing_time_ms' => $duration,
                        'memory_usage' => memory_get_usage(true),
                        'timestamp' => microtime(true)
                    ]
                );
            }
        );

        // Memory intensive endpoint
        $this->app->get(
            '/load/memory-intensive',
            function ($req, $res) {
                $start = microtime(true);

            // Create large data structures
                $largeArray = [];
                for ($i = 0; $i < 10000; $i++) {
                    $largeArray[] = [
                        'id' => $i,
                        'data' => str_repeat("x", 100),
                        'metadata' => array_fill(0, 10, uniqid())
                    ];
                }

                $duration = (microtime(true) - $start) * 1000;

                return $res->json(
                    [
                        'array_size' => count($largeArray),
                        'processing_time_ms' => $duration,
                        'memory_usage' => memory_get_usage(true),
                        'peak_memory' => memory_get_peak_usage(true),
                        'sample_data' => array_slice($largeArray, 0, 3)
                    ]
                );
            }
        );

        // JSON pooling stress test
        $this->app->get(
            '/load/json-stress/:size',
            function ($req, $res) {
                $size = min((int) $req->param('size'), 1000); // Limit size for safety
                $data = $this->createLargeJsonPayload($size);

                return $res->json(
                    [
                        'data_size' => count($data),
                        'pooling_stats' => JsonBufferPool::getStatistics(),
                        'large_dataset' => $data,
                        'memory_usage' => memory_get_usage(true)
                    ]
                );
            }
        );

        // Error simulation endpoint
        $this->app->get(
            '/load/error-simulation/:type',
            function ($req, $res) {
                $type = $req->param('type');

                switch ($type) {
                    case 'exception':
                        throw new \RuntimeException('Simulated load test exception');
                    case 'memory':
                        // Simulate memory pressure
                        $data = str_repeat('x', 1024 * 1024); // 1MB string
                        return $res->status(507)->json(['error' => 'Memory pressure simulation']);
                    case 'timeout':
                        // Simulate slow response
                        usleep(100000); // 100ms delay
                        return $res->status(408)->json(['error' => 'Timeout simulation']);
                    default:
                        return $res->status(400)->json(['error' => 'Unknown error type']);
                }
            }
        );

        // Counter endpoint for concurrency testing
        if (!isset($GLOBALS['load_counter'])) {
            $GLOBALS['load_counter'] = 0;
        }

        $this->app->get(
            '/load/counter',
            function ($req, $res) {
                $GLOBALS['load_counter']++;
                $currentCount = $GLOBALS['load_counter'];

                return $res->json(
                    [
                        'counter' => $currentCount,
                        'timestamp' => microtime(true),
                        'memory' => memory_get_usage(true)
                    ]
                );
            }
        );
    }

    /**
     * Reset load testing metrics
     */
    private function resetLoadMetrics(): void
    {
        $this->loadMetrics = [
            'requests_sent' => 0,
            'requests_completed' => 0,
            'requests_failed' => 0,
            'total_response_time' => 0,
            'min_response_time' => PHP_FLOAT_MAX,
            'max_response_time' => 0,
            'memory_usage_samples' => [],
            'error_types' => [],
            'throughput_rps' => 0
        ];

        // Reset global counter
        $GLOBALS['load_counter'] = 0;
    }

    /**
     * Test basic concurrent request handling
     */
    public function testBasicConcurrentRequestHandling(): void
    {
        $concurrentRequests = 20;
        $responses = [];
        $startTime = microtime(true);

        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $response = $this->simulateRequest('GET', '/load/simple');
            $responses[] = $response;
            $this->loadMetrics['requests_sent']++;
        }

        $totalTime = microtime(true) - $startTime;

        // Validate all responses
        $successCount = 0;
        foreach ($responses as $response) {
            if ($response->getStatusCode() === 200) {
                $successCount++;
                $this->loadMetrics['requests_completed']++;
            } else {
                $this->loadMetrics['requests_failed']++;
            }
        }

        // Calculate metrics
        $this->loadMetrics['throughput_rps'] = $concurrentRequests / $totalTime;

        // Assertions
        $this->assertGreaterThan(0, $successCount, 'At least some requests should succeed');
        $this->assertEquals($concurrentRequests, count($responses), 'All requests should be sent');
        $this->assertGreaterThan(0, $this->loadMetrics['throughput_rps'], 'Throughput should be measurable');

        // Performance assertion
        $this->assertLessThan(5.0, $totalTime, 'Simple concurrent requests should complete within 5 seconds');
    }

    /**
     * Test performance under CPU intensive load
     */
    public function testCpuIntensiveLoadHandling(): void
    {
        $requests = 10;
        $responses = [];
        $processingTimes = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $requests; $i++) {
            $response = $this->simulateRequest('GET', '/load/cpu-intensive');
            $responses[] = $response;

            if ($response->getStatusCode() === 200) {
                $data = $response->getJsonData();
                if (isset($data['processing_time_ms'])) {
                    $processingTimes[] = $data['processing_time_ms'];
                }
            }
        }

        $totalTime = microtime(true) - $startTime;

        // Validate responses
        $successCount = array_filter($responses, fn($r) => $r->getStatusCode() === 200);
        $this->assertGreaterThan(0, count($successCount), 'Some CPU intensive requests should succeed');

        // Analyze processing times
        if (!empty($processingTimes)) {
            $avgProcessingTime = array_sum($processingTimes) / count($processingTimes);
            $maxProcessingTime = max($processingTimes);

            $this->assertLessThan(1000, $avgProcessingTime, 'Average CPU processing time should be reasonable');
            $this->assertLessThan(2000, $maxProcessingTime, 'Max CPU processing time should be under 2 seconds');
        }

        // Overall performance
        $this->assertLessThan(30, $totalTime, 'CPU intensive load test should complete within 30 seconds');
    }

    /**
     * Test memory management under stress
     */
    public function testMemoryManagementUnderStress(): void
    {
        $initialMemory = memory_get_usage(true);
        $requests = 15;
        $memoryUsages = [];

        for ($i = 0; $i < $requests; $i++) {
            $response = $this->simulateRequest('GET', '/load/memory-intensive');

            if ($response->getStatusCode() === 200) {
                $data = $response->getJsonData();
                if (isset($data['memory_usage'])) {
                    $memoryUsages[] = $data['memory_usage'];
                }
            }

            // Force garbage collection periodically
            if ($i % 5 === 0) {
                gc_collect_cycles();
            }
        }

        $finalMemory = memory_get_usage(true);
        $memoryGrowth = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Validate memory management
        $this->assertNotEmpty($memoryUsages, 'Should collect memory usage data');
        $this->assertLessThan(100, $memoryGrowth, 'Memory growth should be reasonable (< 100MB)');

        // Check for memory leaks (growth should stabilize)
        if (count($memoryUsages) > 5) {
            $halfPoint = intval(count($memoryUsages) / 2);
            $firstHalf = array_slice($memoryUsages, 0, $halfPoint);
            $secondHalf = array_slice($memoryUsages, $halfPoint);

            $avgFirstHalf = array_sum($firstHalf) / count($firstHalf);
            $avgSecondHalf = array_sum($secondHalf) / count($secondHalf);

            $memoryIncrease = ($avgSecondHalf - $avgFirstHalf) / 1024 / 1024; // MB
            $this->assertLessThan(50, $memoryIncrease, 'Memory should not increase excessively between test halves');
        }
    }

    /**
     * Test JSON pooling performance under load
     */
    public function testJsonPoolingPerformanceUnderLoad(): void
    {
        // Enable high performance mode for JSON pooling
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $initialStats = JsonBufferPool::getStatistics();
        $requests = 20;
        $dataSizes = [10, 25, 50, 25, 10]; // Varying data sizes

        $responses = [];
        $poolingStats = [];

        foreach ($dataSizes as $size) {
            for ($i = 0; $i < $requests / count($dataSizes); $i++) {
                $response = $this->simulateRequest('GET', "/load/json-stress/{$size}");
                $responses[] = $response;

                if ($response->getStatusCode() === 200) {
                    $data = $response->getJsonData();
                    if (isset($data['pooling_stats'])) {
                        $poolingStats[] = $data['pooling_stats'];
                    }
                }
            }
        }

        $finalStats = JsonBufferPool::getStatistics();

        // Validate JSON pooling effectiveness
        $successfulResponses = array_filter($responses, fn($r) => $r->getStatusCode() === 200);
        $this->assertGreaterThan(0, count($successfulResponses), 'JSON pooling requests should succeed');

        // Check pooling efficiency
        if (isset($finalStats['total_operations']) && $finalStats['total_operations'] > 0) {
            $this->assertGreaterThan(0, $finalStats['total_operations'], 'JSON pooling should be active');

            if (isset($finalStats['reuse_rate'])) {
                $this->assertGreaterThanOrEqual(0, $finalStats['reuse_rate'], 'Reuse rate should be non-negative');
                $this->assertLessThanOrEqual(100, $finalStats['reuse_rate'], 'Reuse rate should not exceed 100%');
            }
        }

        // Verify HP mode is still active
        $hpStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($hpStatus['enabled'], 'High Performance Mode should remain active');
    }

    /**
     * Test error handling under load
     */
    public function testErrorHandlingUnderLoad(): void
    {
        $errorTypes = ['exception', 'memory', 'timeout'];
        $requestsPerType = 5;
        $errorCounts = [];

        foreach ($errorTypes as $errorType) {
            $errorCounts[$errorType] = ['total' => 0, 'handled' => 0];

            for ($i = 0; $i < $requestsPerType; $i++) {
                $response = $this->simulateRequest('GET', "/load/error-simulation/{$errorType}");
                $errorCounts[$errorType]['total']++;

                // Check if error was handled gracefully (non-500 status or proper error response)
                if ($response->getStatusCode() !== 500 || !empty($response->getBody())) {
                    $errorCounts[$errorType]['handled']++;
                }
            }
        }

        // Validate error handling
        foreach ($errorTypes as $errorType) {
            $this->assertGreaterThan(
                0,
                $errorCounts[$errorType]['total'],
                "Should send requests for error type: {$errorType}"
            );
            $this->assertGreaterThan(
                0,
                $errorCounts[$errorType]['handled'],
                "Should handle errors gracefully for type: {$errorType}"
            );
        }

        // Check that application is still responsive after errors
        $healthCheck = $this->simulateRequest('GET', '/load/simple');
        $this->assertEquals(
            200,
            $healthCheck->getStatusCode(),
            'Application should remain responsive after error scenarios'
        );
    }

    /**
     * Test throughput measurement and limits
     */
    public function testThroughputMeasurementAndLimits(): void
    {
        $testDuration = 3; // seconds
        $requestInterval = 0.1; // 100ms between requests
        $maxRequests = intval($testDuration / $requestInterval);

        $startTime = microtime(true);
        $responses = [];
        $requestTimes = [];

        for ($i = 0; $i < $maxRequests; $i++) {
            $requestStart = microtime(true);
            $response = $this->simulateRequest('GET', '/load/counter');
            $requestEnd = microtime(true);

            $responses[] = $response;
            $requestTimes[] = ($requestEnd - $requestStart) * 1000; // Convert to ms

            // Check if we've exceeded test duration
            if ((microtime(true) - $startTime) >= $testDuration) {
                break;
            }

            // Small delay to control request rate
            usleep(intval($requestInterval * 1000000));
        }

        $totalTime = microtime(true) - $startTime;
        $actualRequests = count($responses);

        // Calculate metrics
        $successfulRequests = array_filter($responses, fn($r) => $r->getStatusCode() === 200);
        $successCount = count($successfulRequests);
        $throughput = $successCount / $totalTime;
        $avgResponseTime = array_sum($requestTimes) / count($requestTimes);
        $maxResponseTime = max($requestTimes);

        // Validate throughput metrics
        $this->assertGreaterThan(0, $throughput, 'Throughput should be measurable');
        $this->assertGreaterThan(0, $successCount, 'Some requests should succeed');
        $this->assertLessThan(1000, $avgResponseTime, 'Average response time should be reasonable');
        $this->assertLessThan(2000, $maxResponseTime, 'Max response time should be acceptable');

        // Check counter consistency (if successful)
        if ($successCount > 0) {
            $lastResponse = end($successfulRequests);
            $lastData = $lastResponse->getJsonData();
            if (isset($lastData['counter'])) {
                $this->assertGreaterThan(0, $lastData['counter'], 'Counter should increment');
                $this->assertLessThanOrEqual(
                    $successCount,
                    $lastData['counter'],
                    'Counter should not exceed successful requests'
                );
            }
        }
    }

    /**
     * Test system recovery after stress
     */
    public function testSystemRecoveryAfterStress(): void
    {
        // Phase 1: Apply stress
        $stressRequests = 30;
        $stressResponses = [];

        for ($i = 0; $i < $stressRequests; $i++) {
            // Mix of different endpoint types
            $endpoint = match ($i % 4) {
                0 => '/load/simple',
                1 => '/load/cpu-intensive',
                2 => '/load/memory-intensive',
                3 => '/load/json-stress/20'
            };

            $stressResponses[] = $this->simulateRequest('GET', $endpoint);
        }

        // Phase 2: Force cleanup
        gc_collect_cycles();
        HighPerformanceMode::disable();
        JsonBufferPool::clearPools();
        usleep(500000); // 500ms recovery time

        // Phase 3: Test recovery
        $recoveryRequests = 10;
        $recoveryResponses = [];

        for ($i = 0; $i < $recoveryRequests; $i++) {
            $recoveryResponses[] = $this->simulateRequest('GET', '/load/simple');
        }

        // Validate recovery
        $stressSuccessCount = count(array_filter($stressResponses, fn($r) => $r->getStatusCode() === 200));
        $recoverySuccessCount = count(array_filter($recoveryResponses, fn($r) => $r->getStatusCode() === 200));

        $this->assertGreaterThan(0, $stressSuccessCount, 'Some stress requests should succeed');
        $this->assertGreaterThan(0, $recoverySuccessCount, 'Recovery requests should succeed');

        // Recovery should be at least as good as stress performance
        $stressSuccessRate = $stressSuccessCount / count($stressResponses);
        $recoverySuccessRate = $recoverySuccessCount / count($recoveryResponses);

        $this->assertGreaterThanOrEqual(
            $stressSuccessRate * 0.8,
            $recoverySuccessRate,
            'Recovery success rate should be comparable to stress success rate'
        );
    }

    /**
     * Test performance degradation patterns
     */
    public function testPerformanceDegradationPatterns(): void
    {
        $batchSize = 10;
        $batches = 5;
        $batchMetrics = [];

        for ($batch = 0; $batch < $batches; $batch++) {
            $batchStart = microtime(true);
            $batchResponses = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $response = $this->simulateRequest('GET', '/load/cpu-intensive');
                $batchResponses[] = $response;
            }

            $batchEnd = microtime(true);
            $batchDuration = $batchEnd - $batchStart;

            $successCount = count(array_filter($batchResponses, fn($r) => $r->getStatusCode() === 200));
            $batchThroughput = $successCount / $batchDuration;

            $batchMetrics[] = [
                'batch' => $batch + 1,
                'duration' => $batchDuration,
                'throughput' => $batchThroughput,
                'success_rate' => $successCount / $batchSize,
                'memory_usage' => memory_get_usage(true)
            ];
        }

        // Analyze degradation patterns
        $this->assertCount($batches, $batchMetrics, 'Should collect metrics for all batches');

        // Check for reasonable performance consistency
        $throughputs = array_column($batchMetrics, 'throughput');
        $avgThroughput = array_sum($throughputs) / count($throughputs);
        $maxThroughput = max($throughputs);
        $minThroughput = min($throughputs);

        $this->assertGreaterThan(0, $avgThroughput, 'Average throughput should be positive');

        // Performance should not degrade by more than 50%
        if ($maxThroughput > 0) {
            $degradationRatio = $minThroughput / $maxThroughput;
            $this->assertGreaterThan(
                0.5,
                $degradationRatio,
                'Performance should not degrade by more than 50%'
            );
        }

        // Memory usage should not grow uncontrollably
        $memoryUsages = array_column($batchMetrics, 'memory_usage');
        $memoryGrowth = (max($memoryUsages) - min($memoryUsages)) / 1024 / 1024; // MB
        $this->assertLessThan(50, $memoryGrowth, 'Memory growth should be controlled');
    }

    /**
     * Test concurrent counter consistency
     */
    public function testConcurrentCounterConsistency(): void
    {
        $concurrentRequests = 25;
        $responses = [];
        $counters = [];

        // Reset counter
        $GLOBALS['load_counter'] = 0;

        // Send concurrent requests to counter endpoint
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $response = $this->simulateRequest('GET', '/load/counter');
            $responses[] = $response;

            if ($response->getStatusCode() === 200) {
                $data = $response->getJsonData();
                if (isset($data['counter'])) {
                    $counters[] = $data['counter'];
                }
            }
        }

        // Validate counter consistency
        $this->assertNotEmpty($counters, 'Should collect counter values');
        $this->assertEquals($concurrentRequests, count($responses), 'All requests should be sent');

        // Check counter progression
        sort($counters);
        $uniqueCounters = array_unique($counters);

        // In a concurrent scenario, we expect some counter values
        $this->assertGreaterThan(0, count($uniqueCounters), 'Should have counter progression');
        $this->assertLessThanOrEqual(
            $concurrentRequests,
            max($counters),
            'Max counter should not exceed total requests'
        );

        // Final counter check
        $finalResponse = $this->simulateRequest('GET', '/load/counter');
        if ($finalResponse->getStatusCode() === 200) {
            $finalData = $finalResponse->getJsonData();
            $finalCounter = $finalData['counter'] ?? 0;
            $this->assertEquals(
                $concurrentRequests + 1,
                $finalCounter,
                'Final counter should account for all requests'
            );
        }
    }

    /**
     * Test memory efficiency across all load scenarios
     */
    public function testMemoryEfficiencyAcrossLoadScenarios(): void
    {
        $initialMemory = memory_get_usage(true);
        $scenarios = [
            ['endpoint' => '/load/simple', 'requests' => 15],
            ['endpoint' => '/load/cpu-intensive', 'requests' => 8],
            ['endpoint' => '/load/memory-intensive', 'requests' => 5],
            ['endpoint' => '/load/json-stress/15', 'requests' => 10]
        ];

        $scenarioMetrics = [];

        foreach ($scenarios as $scenario) {
            $scenarioStart = microtime(true);
            $scenarioMemStart = memory_get_usage(true);

            for ($i = 0; $i < $scenario['requests']; $i++) {
                $this->simulateRequest('GET', $scenario['endpoint']);
            }

            $scenarioEnd = microtime(true);
            $scenarioMemEnd = memory_get_usage(true);

            $scenarioMetrics[] = [
                'endpoint' => $scenario['endpoint'],
                'duration' => $scenarioEnd - $scenarioStart,
                'memory_delta' => ($scenarioMemEnd - $scenarioMemStart) / 1024 / 1024, // MB
                'requests' => $scenario['requests']
            ];

            // Force cleanup between scenarios
            gc_collect_cycles();
        }

        $finalMemory = memory_get_usage(true);
        $totalMemoryGrowth = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Validate memory efficiency
        $this->assertCount(count($scenarios), $scenarioMetrics, 'Should collect metrics for all scenarios');
        $this->assertLessThan(75, $totalMemoryGrowth, 'Total memory growth should be reasonable');

        // Check per-scenario memory usage
        foreach ($scenarioMetrics as $metric) {
            $this->assertLessThan(
                30,
                $metric['memory_delta'],
                "Memory delta for {$metric['endpoint']} should be reasonable"
            );
        }

        // Final cleanup and verification
        gc_collect_cycles();
        $cleanupMemory = memory_get_usage(true);
        $postCleanupGrowth = ($cleanupMemory - $initialMemory) / 1024 / 1024; // MB

        // Memory cleanup should be reasonable - allow for some residual growth
        $this->assertLessThanOrEqual(
            $totalMemoryGrowth + 5,
            $postCleanupGrowth,
            'Memory usage should not increase significantly after cleanup'
        );
    }
}
