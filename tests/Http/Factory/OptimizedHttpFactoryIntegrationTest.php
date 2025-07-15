<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Factory;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Pool\Psr7Pool;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Integration tests for OptimizedHttpFactory with Psr7Pool
 *
 * @group factories
 * @group pools
 * @group integration
 */
class OptimizedHttpFactoryIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        OptimizedHttpFactory::reset();
        Psr7Pool::clearPools();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        OptimizedHttpFactory::reset();
        Psr7Pool::clearPools();
    }

    /**
     * Test integration between OptimizedHttpFactory and Psr7Pool
     */
    public function testFactoryPoolIntegration(): void
    {
        OptimizedHttpFactory::enablePooling();

        // Create objects through factory
        $stream1 = OptimizedHttpFactory::createStream('test content 1');
        $stream2 = OptimizedHttpFactory::createStream('test content 2');
        $uri1 = OptimizedHttpFactory::createUri('http://example.com/1');
        $uri2 = OptimizedHttpFactory::createUri('http://example.com/2');

        // Return to pool
        OptimizedHttpFactory::returnToPool($stream1);
        OptimizedHttpFactory::returnToPool($uri1);

        // Get initial pool stats
        $initialStats = Psr7Pool::getStats();

        // Create new objects - should reuse from pool
        $stream3 = OptimizedHttpFactory::createStream('test content 3');
        $uri3 = OptimizedHttpFactory::createUri('http://example.com/3');

        // Get final pool stats
        $finalStats = Psr7Pool::getStats();

        // Verify pool reuse increased
        $this->assertGreaterThan(
            $initialStats['usage']['streams_reused'],
            $finalStats['usage']['streams_reused']
        );
        $this->assertGreaterThan(
            $initialStats['usage']['uris_reused'],
            $finalStats['usage']['uris_reused']
        );

        // Verify objects are valid
        $this->assertInstanceOf(StreamInterface::class, $stream3);
        $this->assertInstanceOf(UriInterface::class, $uri3);

        // Clean up
        OptimizedHttpFactory::returnToPool($stream2);
        OptimizedHttpFactory::returnToPool($stream3);
        OptimizedHttpFactory::returnToPool($uri2);
        OptimizedHttpFactory::returnToPool($uri3);
    }

    /**
     * Test factory performance metrics integration
     */
    public function testFactoryPerformanceMetricsIntegration(): void
    {
        OptimizedHttpFactory::initialize(
            [
                'enable_pooling' => true,
                'enable_metrics' => true,
            ]
        );

        // Create and return objects to generate metrics
        $objects = [];
        for ($i = 0; $i < 20; $i++) {
            $objects[] = OptimizedHttpFactory::createStream("content-$i");
            $objects[] = OptimizedHttpFactory::createUri("http://example.com/$i");
        }

        // Return half to pool
        foreach (array_slice($objects, 0, 20) as $object) {
            OptimizedHttpFactory::returnToPool($object);
        }

        // Get factory metrics
        $factoryMetrics = OptimizedHttpFactory::getPerformanceMetrics();
        $poolStats = OptimizedHttpFactory::getPoolStats();

        // Verify metrics structure
        $this->assertArrayHasKey('memory_usage', $factoryMetrics);
        $this->assertArrayHasKey('pool_efficiency', $factoryMetrics);
        $this->assertArrayHasKey('pool_usage', $factoryMetrics);
        $this->assertArrayHasKey('object_reuse', $factoryMetrics);
        $this->assertArrayHasKey('recommendations', $factoryMetrics);

        // Verify pool stats
        $this->assertArrayHasKey('pool_sizes', $poolStats);
        $this->assertArrayHasKey('efficiency', $poolStats);
        $this->assertArrayHasKey('usage', $poolStats);

        // Verify metrics have meaningful values
        $this->assertGreaterThan(0, $factoryMetrics['memory_usage']['current']);
        $this->assertGreaterThan(0, $factoryMetrics['memory_usage']['peak']);
        $this->assertIsArray($factoryMetrics['recommendations']);

        // Clean up remaining objects
        foreach (array_slice($objects, 20) as $object) {
            OptimizedHttpFactory::returnToPool($object);
        }
    }

    /**
     * Test factory with pool disabled
     */
    public function testFactoryWithPoolDisabled(): void
    {
        // Clear pools first to ensure clean state
        Psr7Pool::clearPools();

        OptimizedHttpFactory::disablePooling();

        // Create objects
        $stream = OptimizedHttpFactory::createStream('test');
        $uri = OptimizedHttpFactory::createUri('http://example.com');

        // Should still create valid objects
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertInstanceOf(UriInterface::class, $uri);

        // Get initial pool stats
        $poolStatsBefore = Psr7Pool::getStats();
        $initialStreamReused = $poolStatsBefore['usage']['streams_reused'];
        $initialUriReused = $poolStatsBefore['usage']['uris_reused'];

        // Return to pool should not affect factory behavior when pooling is disabled
        OptimizedHttpFactory::returnToPool($stream);
        OptimizedHttpFactory::returnToPool($uri);

        // Verify that we can still create objects (pooling disabled doesn't break factory)
        $newStream = OptimizedHttpFactory::createStream('test2');
        $newUri = OptimizedHttpFactory::createUri('http://example2.com');

        $this->assertInstanceOf(StreamInterface::class, $newStream);
        $this->assertInstanceOf(UriInterface::class, $newUri);

        // Verify pooling is disabled
        $this->assertFalse(OptimizedHttpFactory::isPoolingEnabled());
    }

    /**
     * Test factory warm-up with pool integration
     */
    public function testFactoryWarmUpIntegration(): void
    {
        OptimizedHttpFactory::initialize(
            [
                'enable_pooling' => true,
                'warm_up_pools' => true,
            ]
        );

        // After warm-up, pool should have objects
        $stats = Psr7Pool::getStats();

        $this->assertGreaterThan(0, $stats['pool_sizes']['requests']);
        $this->assertGreaterThan(0, $stats['pool_sizes']['responses']);
        $this->assertGreaterThan(0, $stats['pool_sizes']['streams']);
        $this->assertGreaterThan(0, $stats['pool_sizes']['uris']);
    }

    /**
     * Test factory recommendations generation
     */
    public function testFactoryRecommendationsGeneration(): void
    {
        OptimizedHttpFactory::initialize(
            [
                'enable_pooling' => true,
                'enable_metrics' => true,
            ]
        );

        // Generate low efficiency scenario
        for ($i = 0; $i < 50; $i++) {
            OptimizedHttpFactory::createStream("content-$i");
            OptimizedHttpFactory::createUri("http://example.com/$i");
        }

        $metrics = OptimizedHttpFactory::getPerformanceMetrics();

        $this->assertArrayHasKey('recommendations', $metrics);
        $this->assertIsArray($metrics['recommendations']);
        $this->assertNotEmpty($metrics['recommendations']);

        // Recommendations should be strings
        foreach ($metrics['recommendations'] as $recommendation) {
            $this->assertIsString($recommendation);
        }
    }

    /**
     * Test factory stress with pool limits
     */
    public function testFactoryStressWithPoolLimits(): void
    {
        OptimizedHttpFactory::initialize(
            [
                'enable_pooling' => true,
                'max_pool_size' => 10,
            ]
        );

        $objects = [];

        // Create many objects
        for ($i = 0; $i < 100; $i++) {
            $objects[] = OptimizedHttpFactory::createStream("stress-test-$i");
        }

        // Return all to pool
        foreach ($objects as $object) {
            OptimizedHttpFactory::returnToPool($object);
        }

        // Pool should respect limits
        $stats = Psr7Pool::getStats();
        $this->assertLessThanOrEqual(50, $stats['pool_sizes']['streams']); // Psr7Pool MAX_POOL_SIZE

        // Factory should still work
        $newStream = OptimizedHttpFactory::createStream('after-stress');
        $this->assertInstanceOf(StreamInterface::class, $newStream);

        OptimizedHttpFactory::returnToPool($newStream);
    }

    /**
     * Test factory reset with pool integration
     */
    public function testFactoryResetWithPoolIntegration(): void
    {
        OptimizedHttpFactory::initialize(
            [
                'enable_pooling' => true,
                'enable_metrics' => true,
            ]
        );

        // Create objects and populate pool
        $stream = OptimizedHttpFactory::createStream('test');
        $uri = OptimizedHttpFactory::createUri('http://example.com');

        OptimizedHttpFactory::returnToPool($stream);
        OptimizedHttpFactory::returnToPool($uri);

        // Verify pool has objects
        $statsBefore = Psr7Pool::getStats();
        $this->assertGreaterThan(0, $statsBefore['pool_sizes']['streams']);
        $this->assertGreaterThan(0, $statsBefore['pool_sizes']['uris']);

        // Reset factory
        OptimizedHttpFactory::reset();

        // Pool should be cleared
        $statsAfter = Psr7Pool::getStats();
        $this->assertEquals(0, $statsAfter['pool_sizes']['streams']);
        $this->assertEquals(0, $statsAfter['pool_sizes']['uris']);

        // Factory should work with default config
        $config = OptimizedHttpFactory::getConfig();
        $this->assertTrue($config['enable_pooling']);
        $this->assertTrue($config['enable_metrics']);

        $newStream = OptimizedHttpFactory::createStream('after-reset');
        $this->assertInstanceOf(StreamInterface::class, $newStream);
    }

    /**
     * Test factory with mixed object types
     */
    public function testFactoryWithMixedObjectTypes(): void
    {
        OptimizedHttpFactory::enablePooling();

        // Create mix of hybrid and PSR-7 objects
        $hybridRequest = OptimizedHttpFactory::createRequest('GET', '/test', 'handler');
        $hybridResponse = OptimizedHttpFactory::createResponse();
        $psrRequest = OptimizedHttpFactory::createServerRequest('POST', '/api');
        $psrResponse = OptimizedHttpFactory::createPsr7Response(201);
        $stream = OptimizedHttpFactory::createStream('mixed test');
        $uri = OptimizedHttpFactory::createUri('http://mixed.example.com');

        // Verify object types
        $this->assertInstanceOf(Request::class, $hybridRequest);
        $this->assertInstanceOf(Response::class, $hybridResponse);
        $this->assertInstanceOf(ServerRequestInterface::class, $psrRequest);
        $this->assertInstanceOf(ResponseInterface::class, $psrResponse);
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertInstanceOf(UriInterface::class, $uri);

        // Return PSR-7 objects to pool
        OptimizedHttpFactory::returnToPool($psrRequest);
        OptimizedHttpFactory::returnToPool($psrResponse);
        OptimizedHttpFactory::returnToPool($stream);
        OptimizedHttpFactory::returnToPool($uri);

        // Get pool stats
        $stats = Psr7Pool::getStats();

        // Verify pool received objects
        $this->assertGreaterThan(0, $stats['pool_sizes']['requests']);
        $this->assertGreaterThan(0, $stats['pool_sizes']['responses']);
        $this->assertGreaterThan(0, $stats['pool_sizes']['streams']);
        $this->assertGreaterThan(0, $stats['pool_sizes']['uris']);
    }

    /**
     * Test factory configuration persistence
     */
    public function testFactoryConfigurationPersistence(): void
    {
        $customConfig = [
            'enable_pooling' => false,
            'enable_metrics' => false,
            'max_pool_size' => 25,
            'warm_up_pools' => false,
        ];

        OptimizedHttpFactory::initialize($customConfig);

        // Create objects with custom config
        $stream = OptimizedHttpFactory::createStream('config test');
        $uri = OptimizedHttpFactory::createUri('http://config.example.com');

        // Verify config is maintained
        $config = OptimizedHttpFactory::getConfig();
        $this->assertFalse($config['enable_pooling']);
        $this->assertFalse($config['enable_metrics']);
        $this->assertEquals(25, $config['max_pool_size']);
        $this->assertFalse($config['warm_up_pools']);

        // Verify metrics are disabled
        $metrics = OptimizedHttpFactory::getPerformanceMetrics();
        $this->assertTrue($metrics['metrics_disabled']);

        $stats = OptimizedHttpFactory::getPoolStats();
        $this->assertTrue($stats['metrics_disabled']);

        // Objects should still be created
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertInstanceOf(UriInterface::class, $uri);
    }
}
