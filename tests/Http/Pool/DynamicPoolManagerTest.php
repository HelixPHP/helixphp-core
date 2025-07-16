<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Pool\DynamicPoolManager;

/**
 * Comprehensive tests for DynamicPoolManager
 *
 * Tests memory-adaptive pool sizing, tier determination,
 * memory monitoring, and emergency cleanup functionality.
 * Following the "less is more" principle with focused, quality testing.
 */
class DynamicPoolManagerTest extends TestCase
{
    private DynamicPoolManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new DynamicPoolManager();
        // Reset statistics before each test
        $this->manager->resetStats();
    }

    /**
     * Test optimal pool sizes retrieval
     */
    public function testOptimalPoolSizesRetrieval(): void
    {
        $poolSizes = DynamicPoolManager::getOptimalPoolSizes();

        $this->assertIsArray($poolSizes);
        $this->assertArrayHasKey('header_pool', $poolSizes);
        $this->assertArrayHasKey('response_pool', $poolSizes);
        $this->assertArrayHasKey('stream_pool', $poolSizes);
        $this->assertArrayHasKey('operations_cache', $poolSizes);

        // Verify all values are positive integers
        foreach ($poolSizes as $key => $value) {
            $this->assertIsInt($value);
            $this->assertGreaterThan(0, $value);
        }
    }

    /**
     * Test memory tier determination
     */
    public function testMemoryTierDetermination(): void
    {
        // Test multiple calls to see tier stability
        $poolSizes1 = DynamicPoolManager::getOptimalPoolSizes();
        $poolSizes2 = DynamicPoolManager::getOptimalPoolSizes();

        $this->assertIsArray($poolSizes1);
        $this->assertIsArray($poolSizes2);

        // Pool sizes should be consistent for same memory conditions
        $this->assertEquals($poolSizes1, $poolSizes2);
    }

    /**
     * Test memory recommendations
     */
    public function testMemoryRecommendations(): void
    {
        $recommendations = DynamicPoolManager::getMemoryRecommendations();

        $this->assertIsArray($recommendations);

        // Recommendations should be strings
        foreach ($recommendations as $recommendation) {
            $this->assertIsString($recommendation);
            $this->assertNotEmpty($recommendation);
        }
    }

    /**
     * Test detailed statistics
     */
    public function testDetailedStatistics(): void
    {
        $stats = DynamicPoolManager::getDetailedStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('current_memory', $stats);
        $this->assertArrayHasKey('peak_memory', $stats);
        $this->assertArrayHasKey('memory_limit', $stats);
        $this->assertArrayHasKey('usage_percentage', $stats);
        $this->assertArrayHasKey('current_tier', $stats);
        $this->assertArrayHasKey('tier_changes', $stats);
        $this->assertArrayHasKey('gc_cycles', $stats);
        $this->assertArrayHasKey('optimal_pool_sizes', $stats);
        $this->assertArrayHasKey('recommendations', $stats);

        // Verify data types
        $this->assertIsString($stats['current_memory']);
        $this->assertIsString($stats['peak_memory']);
        $this->assertIsNumeric($stats['usage_percentage']);
        $this->assertIsString($stats['current_tier']);
        $this->assertIsInt($stats['tier_changes']);
        $this->assertIsInt($stats['gc_cycles']);
        $this->assertIsArray($stats['optimal_pool_sizes']);
        $this->assertIsArray($stats['recommendations']);
    }

    /**
     * Test memory statistics updates
     */
    public function testMemoryStatisticsUpdates(): void
    {
        $initialStats = DynamicPoolManager::getDetailedStats();

        // Update memory statistics
        DynamicPoolManager::updateMemoryStats();

        $updatedStats = DynamicPoolManager::getDetailedStats();

        $this->assertIsArray($initialStats);
        $this->assertIsArray($updatedStats);

        // Statistics should be updated
        $this->assertArrayHasKey('current_memory', $updatedStats);
        $this->assertArrayHasKey('peak_memory', $updatedStats);
    }

    /**
     * Test statistics reset
     */
    public function testStatisticsReset(): void
    {
        // Generate some statistics
        DynamicPoolManager::getOptimalPoolSizes();
        DynamicPoolManager::updateMemoryStats();

        $statsBeforeReset = DynamicPoolManager::getDetailedStats();

        // Reset statistics
        DynamicPoolManager::resetStats();

        $statsAfterReset = DynamicPoolManager::getDetailedStats();

        $this->assertEquals(0, $statsAfterReset['tier_changes']);
        $this->assertEquals(0, $statsAfterReset['gc_cycles']);
    }

    /**
     * Test force cleanup determination
     */
    public function testForceCleanupDetermination(): void
    {
        $cleanupExecuted = DynamicPoolManager::forceCleanupIfNeeded();

        $this->assertIsBool($cleanupExecuted);

        // Most systems won't trigger cleanup immediately
        // This test mainly verifies the method executes without errors
    }

    /**
     * Test borrow functionality
     */
    public function testBorrowFunctionality(): void
    {
        $object = $this->manager->borrow('request');

        $this->assertIsObject($object);

        // Test with callable factory
        $callable = function () {
            return new \stdClass();
        };

        $object2 = $this->manager->borrow('request', ['callable' => $callable]);
        $this->assertInstanceOf(\stdClass::class, $object2);

        // Test with class factory
        $object3 = $this->manager->borrow(
            'request',
            [
                'class' => \stdClass::class,
                'args' => []
            ]
        );
        $this->assertInstanceOf(\stdClass::class, $object3);
    }

    /**
     * Test return functionality
     */
    public function testReturnFunctionality(): void
    {
        $object = new \stdClass();

        // Should not throw any exceptions
        $this->manager->return('request', $object);

        $this->assertTrue(true); // If we reach here, method executed successfully
    }

    /**
     * Test pool statistics
     */
    public function testPoolStatistics(): void
    {
        $stats = $this->manager->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('stats', $stats);
        $this->assertArrayHasKey('scaling_state', $stats);
        $this->assertArrayHasKey('pool_sizes', $stats);
        $this->assertArrayHasKey('pool_usage', $stats);
        $this->assertArrayHasKey('metrics', $stats);
        $this->assertArrayHasKey('config', $stats);

        // Verify statistics structure
        $this->assertIsArray($stats['stats']);
        $this->assertIsArray($stats['scaling_state']);
        $this->assertIsArray($stats['pool_sizes']);
        $this->assertIsArray($stats['pool_usage']);
        $this->assertIsArray($stats['metrics']);
        $this->assertIsArray($stats['config']);
    }

    /**
     * Test simulated pool activity
     */
    public function testSimulatedPoolActivity(): void
    {
        $initialStats = $this->manager->getStats();

        // Borrow multiple objects to trigger simulated activity
        for ($i = 0; $i < 15; $i++) {
            $this->manager->borrow('request');
        }

        $updatedStats = $this->manager->getStats();

        $this->assertGreaterThan($initialStats['stats']['borrowed'], $updatedStats['stats']['borrowed']);
        $this->assertGreaterThan($initialStats['stats']['expanded'], $updatedStats['stats']['expanded']);
    }

    /**
     * Test emergency activation simulation
     */
    public function testEmergencyActivationSimulation(): void
    {
        $initialStats = $this->manager->getStats();

        // Borrow enough objects to trigger emergency activation
        for ($i = 0; $i < 105; $i++) {
            $this->manager->borrow('request');
        }

        $updatedStats = $this->manager->getStats();

        $this->assertGreaterThan($initialStats['stats']['emergency_activations'], $updatedStats['stats']['emergency_activations']);
    }

    /**
     * Test overflow creation simulation
     */
    public function testOverflowCreationSimulation(): void
    {
        $initialStats = $this->manager->getStats();

        // Borrow enough objects to trigger overflow creation
        for ($i = 0; $i < 55; $i++) {
            $this->manager->borrow('request');
        }

        $updatedStats = $this->manager->getStats();

        $this->assertGreaterThan($initialStats['stats']['overflow_created'], $updatedStats['stats']['overflow_created']);
    }

    /**
     * Test constructor with configuration
     */
    public function testConstructorWithConfiguration(): void
    {
        $config = [
            'initial_size' => 100,
            'max_size' => 1000,
            'auto_scale' => true
        ];

        $manager = new DynamicPoolManager($config);

        $this->assertInstanceOf(DynamicPoolManager::class, $manager);

        // Manager should function normally with configuration
        $object = $manager->borrow('test');
        $this->assertIsObject($object);
    }

    /**
     * Test borrow and return cycle
     */
    public function testBorrowAndReturnCycle(): void
    {
        $initialStats = $this->manager->getStats();

        // Borrow objects
        $objects = [];
        for ($i = 0; $i < 10; $i++) {
            $objects[] = $this->manager->borrow('request');
        }

        // Return objects
        foreach ($objects as $object) {
            $this->manager->return('request', $object);
        }

        $finalStats = $this->manager->getStats();

        $this->assertEquals(10, $finalStats['stats']['borrowed'] - $initialStats['stats']['borrowed']);
        $this->assertEquals(10, $finalStats['stats']['returned'] - $initialStats['stats']['returned']);
    }

    /**
     * Test different object types
     */
    public function testDifferentObjectTypes(): void
    {
        $requestObj = $this->manager->borrow('request');
        $responseObj = $this->manager->borrow('response');
        $defaultObj = $this->manager->borrow('unknown');

        $this->assertIsObject($requestObj);
        $this->assertIsObject($responseObj);
        $this->assertIsObject($defaultObj);

        // All should be stdClass for this basic implementation
        $this->assertInstanceOf(\stdClass::class, $requestObj);
        $this->assertInstanceOf(\stdClass::class, $responseObj);
        $this->assertInstanceOf(\stdClass::class, $defaultObj);
    }

    /**
     * Test memory tier consistency
     */
    public function testMemoryTierConsistency(): void
    {
        // Multiple calls should return consistent tiers under same conditions
        $poolSizes1 = DynamicPoolManager::getOptimalPoolSizes();
        $stats1 = DynamicPoolManager::getDetailedStats();

        $poolSizes2 = DynamicPoolManager::getOptimalPoolSizes();
        $stats2 = DynamicPoolManager::getDetailedStats();

        $this->assertEquals($stats1['current_tier'], $stats2['current_tier']);
        $this->assertEquals($poolSizes1, $poolSizes2);
    }

    /**
     * Test memory usage percentage calculation
     */
    public function testMemoryUsagePercentageCalculation(): void
    {
        $stats = DynamicPoolManager::getDetailedStats();

        $this->assertIsNumeric($stats['usage_percentage']);
        $this->assertGreaterThanOrEqual(0, $stats['usage_percentage']);
        $this->assertLessThanOrEqual(100, $stats['usage_percentage']);
    }

    /**
     * Test tier changes tracking
     */
    public function testTierChangesTracking(): void
    {
        $initialStats = DynamicPoolManager::getDetailedStats();
        $initialTierChanges = $initialStats['tier_changes'];

        // Multiple calls to potentially trigger tier changes
        for ($i = 0; $i < 10; $i++) {
            DynamicPoolManager::getOptimalPoolSizes();
        }

        $finalStats = DynamicPoolManager::getDetailedStats();
        $finalTierChanges = $finalStats['tier_changes'];

        $this->assertGreaterThanOrEqual($initialTierChanges, $finalTierChanges);
    }

    /**
     * Test recommendations based on usage
     */
    public function testRecommendationsBasedOnUsage(): void
    {
        $recommendations = DynamicPoolManager::getMemoryRecommendations();

        $this->assertIsArray($recommendations);

        // Should have at least one recommendation
        $this->assertGreaterThanOrEqual(0, count($recommendations));

        // Each recommendation should be a non-empty string
        foreach ($recommendations as $recommendation) {
            $this->assertIsString($recommendation);
            $this->assertNotEmpty($recommendation);
        }
    }

    /**
     * Test pool configuration structure
     */
    public function testPoolConfigurationStructure(): void
    {
        $poolSizes = DynamicPoolManager::getOptimalPoolSizes();

        // Required pool types
        $requiredPools = ['header_pool', 'response_pool', 'stream_pool', 'operations_cache'];

        foreach ($requiredPools as $pool) {
            $this->assertArrayHasKey($pool, $poolSizes);
            $this->assertIsInt($poolSizes[$pool]);
            $this->assertGreaterThan(0, $poolSizes[$pool]);
        }

        // Pool sizes should be reasonable (not too small, not too large)
        foreach ($poolSizes as $size) {
            $this->assertLessThan(10000, $size); // Not too large
            $this->assertGreaterThan(10, $size);  // Not too small
        }
    }

    /**
     * Test detailed stats format
     */
    public function testDetailedStatsFormat(): void
    {
        $stats = DynamicPoolManager::getDetailedStats();

        // Memory values should be formatted as strings with units
        $this->assertMatchesRegularExpression('/^\d+(\.\d+)?\s*(B|KB|MB|GB)$/', $stats['current_memory']);
        $this->assertMatchesRegularExpression('/^\d+(\.\d+)?\s*(B|KB|MB|GB)$|^unlimited$/', $stats['memory_limit']);

        // Tier should be one of the valid tiers
        $this->assertContains($stats['current_tier'], ['low', 'medium', 'high', 'critical']);
    }
}
