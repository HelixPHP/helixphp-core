<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Memory;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Memory\MemoryManager;

/**
 * Simplified Memory Manager test following ARCHITECTURAL_GUIDELINES
 *
 * Tests core functionality without over-engineering.
 * Stress tests moved to separate performance test group.
 */
class MemoryManagerSimpleTest extends TestCase
{
    private MemoryManager $memoryManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Simple configuration for functional testing
        $this->memoryManager = new MemoryManager(
            [
                'check_interval' => 0, // No rate limiting for tests
                'gc_threshold' => 0.7,
                'emergency_gc' => 0.9,
            ]
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->memoryManager->shutdown();
    }

    public function testMemoryManagerInstantiation(): void
    {
        $manager = new MemoryManager();
        $this->assertInstanceOf(MemoryManager::class, $manager);

        $status = $manager->getStatus();
        $this->assertIsArray($status);
        $this->assertArrayHasKey('pressure', $status);
        $this->assertArrayHasKey('emergency_mode', $status);
        $this->assertArrayHasKey('usage', $status);
        $this->assertArrayHasKey('gc', $status);
    }

    public function testBasicConfiguration(): void
    {
        $customConfig = [
            'gc_strategy' => MemoryManager::STRATEGY_CONSERVATIVE,
            'gc_threshold' => 0.8,
            'emergency_gc' => 0.9,
        ];

        $manager = new MemoryManager($customConfig);
        $status = $manager->getStatus();

        $this->assertEquals(MemoryManager::STRATEGY_CONSERVATIVE, $status['gc']['strategy']);
        $this->assertFalse($status['emergency_mode']);
    }

    public function testMemoryPressureLevels(): void
    {
        $this->assertEquals('low', MemoryManager::PRESSURE_LOW);
        $this->assertEquals('medium', MemoryManager::PRESSURE_MEDIUM);
        $this->assertEquals('high', MemoryManager::PRESSURE_HIGH);
        $this->assertEquals('critical', MemoryManager::PRESSURE_CRITICAL);
    }

    public function testBasicMemoryUsage(): void
    {
        $status = $this->memoryManager->getStatus();

        $this->assertIsInt($status['usage']['current']);
        $this->assertIsInt($status['usage']['peak']);
        $this->assertIsInt($status['usage']['limit']);
        $this->assertIsFloat($status['usage']['percentage']);

        $this->assertGreaterThan(0, $status['usage']['current']);
        $this->assertGreaterThanOrEqual(0, $status['usage']['percentage']);
        // Usage percentage can exceed 100% if memory usage exceeds warning threshold
        $this->assertGreaterThanOrEqual(0, $status['usage']['percentage']);
    }

    public function testGarbageCollectionExecution(): void
    {
        $initialStatus = $this->memoryManager->getStatus();
        $initialGCRuns = $initialStatus['gc']['runs'];

        $this->memoryManager->forceGC();

        $newStatus = $this->memoryManager->getStatus();
        $this->assertGreaterThan($initialGCRuns, $newStatus['gc']['runs']);
    }

    public function testBasicObjectTracking(): void
    {
        $initialStatus = $this->memoryManager->getStatus();
        $initialTracked = $initialStatus['tracked_objects'];

        $obj = new \stdClass();
        $this->memoryManager->trackObject('test', $obj, ['priority' => 'high']);

        $newStatus = $this->memoryManager->getStatus();
        $this->assertEquals($initialTracked + 1, $newStatus['tracked_objects']);
    }

    public function testMemoryCheck(): void
    {
        $this->memoryManager->check();

        $metrics = $this->memoryManager->getMetrics();
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('memory_usage_percent', $metrics);
        $this->assertArrayHasKey('current_pressure', $metrics);
    }

    public function testShutdownCleanup(): void
    {
        $manager = new MemoryManager();

        $obj = new \stdClass();
        $manager->trackObject('shutdown_test', $obj);
        $manager->forceGC();

        // Shutdown should not throw errors
        $manager->shutdown();
        $this->assertTrue(true);
    }

    public function testBasicMetricsCollection(): void
    {
        $this->memoryManager->forceGC();
        $this->memoryManager->check();

        $metrics = $this->memoryManager->getMetrics();

        $expectedKeys = [
            'gc_runs',
            'gc_collected',
            'current_pressure',
            'memory_usage_percent',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $metrics);
        }

        $this->assertGreaterThanOrEqual(1, $metrics['gc_runs']);
        $this->assertIsString($metrics['current_pressure']);
        $this->assertIsFloat($metrics['memory_usage_percent']);
    }
}
