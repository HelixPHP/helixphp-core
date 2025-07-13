<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Performance;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Memory\MemoryManager;
use PivotPHP\Core\Http\Pool\DynamicPoolManager;

/**
 * Performance and stress tests for MemoryManager
 *
 * @group performance
 * @group stress
 */
class MemoryManagerStressTest extends TestCase
{
    private MemoryManager $memoryManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->memoryManager = new MemoryManager(
            [
                'check_interval' => 0,
                'gc_threshold' => 0.7,
                'emergency_gc' => 0.9,
            ]
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->memoryManager->shutdown();
        gc_collect_cycles();
    }

    /**
     * @group stress
     * @group memory
     */
    public function testMemoryStressTesting(): void
    {
        $initialStatus = $this->memoryManager->getStatus();

        // Create memory pressure
        $largeArrays = [];
        for ($i = 0; $i < 10; $i++) {
            $largeArrays[] = range(1, 5000);
            $this->memoryManager->check();
        }

        $stressStatus = $this->memoryManager->getStatus();

        // Memory usage should have increased or stayed the same
        $this->assertGreaterThanOrEqual($initialStatus['usage']['current'], $stressStatus['usage']['current']);

        unset($largeArrays);
        gc_collect_cycles();
    }

    /**
     * @group stress
     * @group tracking
     */
    public function testObjectTrackingStress(): void
    {
        $initialTracked = $this->memoryManager->getStatus()['tracked_objects'];

        // Track many objects
        $objects = [];
        for ($i = 0; $i < 100; $i++) {
            $obj = new \stdClass();
            $obj->data = str_repeat('x', 100);
            $objects[] = $obj;
            $this->memoryManager->trackObject('stress_test', $obj, ['iteration' => $i]);
        }

        $peakStatus = $this->memoryManager->getStatus();
        $this->assertEquals($initialTracked + 100, $peakStatus['tracked_objects']);

        unset($objects);
        gc_collect_cycles();
        $this->memoryManager->check();

        $this->assertTrue(true); // Test completed without errors
    }

    /**
     * @group performance
     * @group gc
     */
    public function testGCPerformanceMetrics(): void
    {
        // Run multiple GC cycles
        for ($i = 0; $i < 5; $i++) {
            $this->memoryManager->forceGC();
            usleep(1000); // Small delay
        }

        $metrics = $this->memoryManager->getMetrics();

        $this->assertArrayHasKey('gc_runs', $metrics);
        $this->assertArrayHasKey('avg_gc_duration_ms', $metrics);
        $this->assertArrayHasKey('gc_frequency', $metrics);

        $this->assertGreaterThanOrEqual(5, $metrics['gc_runs']);
        $this->assertGreaterThanOrEqual(0, $metrics['avg_gc_duration_ms']);
        $this->assertGreaterThanOrEqual(0, $metrics['gc_frequency']);
    }

    /**
     * @group stress
     * @group workflow
     */
    public function testCompleteMemoryManagementWorkflow(): void
    {
        // Simulate application lifecycle
        $manager = new MemoryManager(
            [
                'gc_strategy' => MemoryManager::STRATEGY_ADAPTIVE,
                'gc_threshold' => 0.7,
                'emergency_gc' => 0.9,
            ]
        );

        // Track application objects
        $request = new \stdClass();
        $response = new \stdClass();
        $manager->trackObject('request', $request, ['method' => 'GET']);
        $manager->trackObject('response', $response, ['status' => 200]);

        // Create workload
        $workload = [];
        for ($i = 0; $i < 20; $i++) {
            $workload[] = range(1, 1000);
            $manager->check();
        }

        $manager->forceGC();

        $finalStatus = $manager->getStatus();
        $finalMetrics = $manager->getMetrics();

        $this->assertIsArray($finalStatus);
        $this->assertIsArray($finalMetrics);
        $this->assertGreaterThan(0, $finalMetrics['gc_runs']);

        unset($workload, $request, $response);
        $manager->shutdown();
    }
}
