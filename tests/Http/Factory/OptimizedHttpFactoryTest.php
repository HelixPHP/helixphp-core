<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Factory;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Pool\Psr7Pool;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Comprehensive tests for OptimizedHttpFactory
 *
 * @group factories
 * @group http
 * @group performance
 */
class OptimizedHttpFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset factory state before each test
        OptimizedHttpFactory::reset();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up factory state after each test
        OptimizedHttpFactory::reset();
    }

    /**
     * Test factory initialization
     */
    public function testInitialization(): void
    {
        // Test default initialization
        OptimizedHttpFactory::initialize();

        $config = OptimizedHttpFactory::getConfig();
        $this->assertTrue($config['enable_pooling']);
        $this->assertTrue($config['warm_up_pools']);
        $this->assertEquals(100, $config['max_pool_size']);
        $this->assertTrue($config['enable_metrics']);
    }

    /**
     * Test factory initialization with custom config
     */
    public function testInitializationWithCustomConfig(): void
    {
        $customConfig = [
            'enable_pooling' => false,
            'warm_up_pools' => false,
            'max_pool_size' => 50,
            'enable_metrics' => false,
        ];

        OptimizedHttpFactory::initialize($customConfig);

        $config = OptimizedHttpFactory::getConfig();
        $this->assertFalse($config['enable_pooling']);
        $this->assertFalse($config['warm_up_pools']);
        $this->assertEquals(50, $config['max_pool_size']);
        $this->assertFalse($config['enable_metrics']);
    }

    /**
     * Test factory initialization idempotence
     */
    public function testInitializationIdempotence(): void
    {
        // First initialization
        OptimizedHttpFactory::initialize(['max_pool_size' => 100]);
        $config1 = OptimizedHttpFactory::getConfig();

        // Second initialization should be ignored
        OptimizedHttpFactory::initialize(['max_pool_size' => 200]);
        $config2 = OptimizedHttpFactory::getConfig();

        $this->assertEquals($config1, $config2);
        $this->assertEquals(100, $config2['max_pool_size']);
    }

    /**
     * Test hybrid Request creation
     */
    public function testCreateRequest(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/test', 'test-callable');

        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getPath());
    }

    /**
     * Test hybrid Response creation
     */
    public function testCreateResponse(): void
    {
        $response = OptimizedHttpFactory::createResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test PSR-7 ServerRequest creation
     */
    public function testCreateServerRequest(): void
    {
        $serverRequest = OptimizedHttpFactory::createServerRequest(
            'POST',
            '/api/test',
            ['REQUEST_METHOD' => 'POST'],
            ['Content-Type' => 'application/json']
        );

        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequest);
        $this->assertEquals('POST', $serverRequest->getMethod());
        $this->assertEquals('/api/test', (string) $serverRequest->getUri());
        $this->assertIsArray($serverRequest->getServerParams());
    }

    /**
     * Test PSR-7 Response creation
     */
    public function testCreatePsr7Response(): void
    {
        $response = OptimizedHttpFactory::createPsr7Response(
            201,
            ['Content-Type' => 'application/json'],
            '{"status": "created"}'
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('{"status": "created"}', (string) $response->getBody());
    }

    /**
     * Test Stream creation
     */
    public function testCreateStream(): void
    {
        $stream = OptimizedHttpFactory::createStream('test content');

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals('test content', (string) $stream);
    }

    /**
     * Test Stream creation with empty content
     */
    public function testCreateStreamEmpty(): void
    {
        $stream = OptimizedHttpFactory::createStream();

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals('', (string) $stream);
    }

    /**
     * Test Stream creation from file
     */
    public function testCreateStreamFromFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_stream');
        file_put_contents($tempFile, 'file content');

        try {
            $stream = OptimizedHttpFactory::createStreamFromFile($tempFile);

            $this->assertInstanceOf(StreamInterface::class, $stream);
            $this->assertEquals('file content', (string) $stream);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test Uri creation
     */
    public function testCreateUri(): void
    {
        $uri = OptimizedHttpFactory::createUri('https://example.com/path');

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertEquals('https://example.com/path', (string) $uri);
    }

    /**
     * Test Uri creation with empty string
     */
    public function testCreateUriEmpty(): void
    {
        $uri = OptimizedHttpFactory::createUri();

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertEquals('', (string) $uri);
    }

    /**
     * Test Request creation from globals
     */
    public function testCreateRequestFromGlobals(): void
    {
        // Backup original globals
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;

        try {
            // Mock globals
            $_SERVER = [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'HTTP_HOST' => 'example.com',
                'SERVER_NAME' => 'example.com',
                'SERVER_PORT' => '80',
            ];
            $_GET = ['param' => 'value'];
            $_POST = [];

            $request = OptimizedHttpFactory::createRequestFromGlobals();

            $this->assertInstanceOf(Request::class, $request);
            $this->assertEquals('GET', $request->getMethod());
        } finally {
            // Restore original globals
            $_SERVER = $originalServer;
            $_GET = $originalGet;
            $_POST = $originalPost;
        }
    }

    /**
     * Test pooling behavior with enabled pooling
     */
    public function testPoolingEnabled(): void
    {
        OptimizedHttpFactory::enablePooling();

        $this->assertTrue(OptimizedHttpFactory::isPoolingEnabled());

        $stream = OptimizedHttpFactory::createStream('test');
        $this->assertInstanceOf(StreamInterface::class, $stream);

        $uri = OptimizedHttpFactory::createUri('http://example.com');
        $this->assertInstanceOf(UriInterface::class, $uri);
    }

    /**
     * Test pooling behavior with disabled pooling
     */
    public function testPoolingDisabled(): void
    {
        OptimizedHttpFactory::disablePooling();

        $this->assertFalse(OptimizedHttpFactory::isPoolingEnabled());

        $stream = OptimizedHttpFactory::createStream('test');
        $this->assertInstanceOf(StreamInterface::class, $stream);

        $uri = OptimizedHttpFactory::createUri('http://example.com');
        $this->assertInstanceOf(UriInterface::class, $uri);
    }

    /**
     * Test manual object return to pool
     */
    public function testReturnToPool(): void
    {
        OptimizedHttpFactory::enablePooling();

        $serverRequest = OptimizedHttpFactory::createServerRequest('GET', '/test');
        $response = OptimizedHttpFactory::createPsr7Response();
        $stream = OptimizedHttpFactory::createStream('test');
        $uri = OptimizedHttpFactory::createUri('http://example.com');

        // Should not throw exceptions
        OptimizedHttpFactory::returnToPool($serverRequest);
        OptimizedHttpFactory::returnToPool($response);
        OptimizedHttpFactory::returnToPool($stream);
        OptimizedHttpFactory::returnToPool($uri);

        $this->assertTrue(true); // If we reach here, no exceptions were thrown
    }

    /**
     * Test return to pool with pooling disabled
     */
    public function testReturnToPoolDisabled(): void
    {
        OptimizedHttpFactory::disablePooling();

        $stream = OptimizedHttpFactory::createStream('test');

        // Should not throw exceptions even with pooling disabled
        OptimizedHttpFactory::returnToPool($stream);

        $this->assertTrue(true);
    }

    /**
     * Test return to pool with invalid object
     */
    public function testReturnToPoolInvalidObject(): void
    {
        OptimizedHttpFactory::enablePooling();

        $invalidObject = new \stdClass();

        // Should not throw exceptions for invalid objects
        OptimizedHttpFactory::returnToPool($invalidObject);

        $this->assertTrue(true);
    }

    /**
     * Test pool clearing
     */
    public function testClearPools(): void
    {
        OptimizedHttpFactory::enablePooling();

        // Create some objects to populate pools
        $stream = OptimizedHttpFactory::createStream('test');
        $uri = OptimizedHttpFactory::createUri('http://example.com');

        OptimizedHttpFactory::returnToPool($stream);
        OptimizedHttpFactory::returnToPool($uri);

        // Clear pools
        OptimizedHttpFactory::clearPools();

        // Should not throw exceptions
        $this->assertTrue(true);
    }

    /**
     * Test pool warm-up
     */
    public function testWarmUpPools(): void
    {
        OptimizedHttpFactory::enablePooling();

        // Should not throw exceptions
        OptimizedHttpFactory::warmUpPools();

        $this->assertTrue(true);
    }

    /**
     * Test configuration updates
     */
    public function testUpdateConfig(): void
    {
        $initialConfig = OptimizedHttpFactory::getConfig();

        OptimizedHttpFactory::updateConfig(
            [
                'max_pool_size' => 200,
                'enable_metrics' => false,
            ]
        );

        $updatedConfig = OptimizedHttpFactory::getConfig();

        $this->assertEquals(200, $updatedConfig['max_pool_size']);
        $this->assertFalse($updatedConfig['enable_metrics']);
        $this->assertEquals($initialConfig['enable_pooling'], $updatedConfig['enable_pooling']);
    }

    /**
     * Test pooling state management
     */
    public function testPoolingStateManagement(): void
    {
        // Test setPoolingEnabled
        OptimizedHttpFactory::setPoolingEnabled(true);
        $this->assertTrue(OptimizedHttpFactory::isPoolingEnabled());

        OptimizedHttpFactory::setPoolingEnabled(false);
        $this->assertFalse(OptimizedHttpFactory::isPoolingEnabled());

        // Test enablePooling/disablePooling
        OptimizedHttpFactory::enablePooling();
        $this->assertTrue(OptimizedHttpFactory::isPoolingEnabled());

        OptimizedHttpFactory::disablePooling();
        $this->assertFalse(OptimizedHttpFactory::isPoolingEnabled());
    }

    /**
     * Test pool statistics with metrics enabled
     */
    public function testPoolStatisticsEnabled(): void
    {
        OptimizedHttpFactory::updateConfig(['enable_metrics' => true]);

        $stats = OptimizedHttpFactory::getPoolStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('pool_sizes', $stats);
        $this->assertArrayHasKey('efficiency', $stats);
        $this->assertArrayHasKey('usage', $stats);
    }

    /**
     * Test pool statistics with metrics disabled
     */
    public function testPoolStatisticsDisabled(): void
    {
        OptimizedHttpFactory::updateConfig(['enable_metrics' => false]);

        $stats = OptimizedHttpFactory::getPoolStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('metrics_disabled', $stats);
        $this->assertTrue($stats['metrics_disabled']);
    }

    /**
     * Test performance metrics with metrics enabled
     */
    public function testPerformanceMetricsEnabled(): void
    {
        OptimizedHttpFactory::updateConfig(['enable_metrics' => true]);

        $metrics = OptimizedHttpFactory::getPerformanceMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('memory_usage', $metrics);
        $this->assertArrayHasKey('pool_efficiency', $metrics);
        $this->assertArrayHasKey('pool_usage', $metrics);
        $this->assertArrayHasKey('object_reuse', $metrics);
        $this->assertArrayHasKey('recommendations', $metrics);

        $this->assertArrayHasKey('current', $metrics['memory_usage']);
        $this->assertArrayHasKey('peak', $metrics['memory_usage']);
        $this->assertIsArray($metrics['recommendations']);
    }

    /**
     * Test performance metrics with metrics disabled
     */
    public function testPerformanceMetricsDisabled(): void
    {
        OptimizedHttpFactory::updateConfig(['enable_metrics' => false]);

        $metrics = OptimizedHttpFactory::getPerformanceMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('metrics_disabled', $metrics);
        $this->assertTrue($metrics['metrics_disabled']);
    }

    /**
     * Test reset functionality
     */
    public function testReset(): void
    {
        // Modify configuration
        OptimizedHttpFactory::updateConfig(
            [
                'enable_pooling' => false,
                'max_pool_size' => 200,
            ]
        );

        // Create some objects
        $stream = OptimizedHttpFactory::createStream('test');
        OptimizedHttpFactory::returnToPool($stream);

        // Reset
        OptimizedHttpFactory::reset();

        // Configuration should be back to defaults
        $config = OptimizedHttpFactory::getConfig();
        $this->assertTrue($config['enable_pooling']);
        $this->assertEquals(100, $config['max_pool_size']);
    }

    /**
     * Test automatic initialization
     */
    public function testAutomaticInitialization(): void
    {
        // Factory should auto-initialize on first use
        $request = OptimizedHttpFactory::createRequest('GET', '/test', 'callable');

        $this->assertInstanceOf(Request::class, $request);

        $config = OptimizedHttpFactory::getConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('enable_pooling', $config);
    }

    /**
     * Test concurrent object creation
     */
    public function testConcurrentObjectCreation(): void
    {
        OptimizedHttpFactory::enablePooling();

        $objects = [];

        // Create multiple objects of different types
        for ($i = 0; $i < 10; $i++) {
            $objects[] = OptimizedHttpFactory::createStream("content-$i");
            $objects[] = OptimizedHttpFactory::createUri("http://example.com/path-$i");
            $objects[] = OptimizedHttpFactory::createPsr7Response(200, [], "body-$i");
        }

        $this->assertCount(30, $objects);

        // Verify all objects are valid
        foreach ($objects as $object) {
            $this->assertIsObject($object);
        }

        // Return all objects to pool
        foreach ($objects as $object) {
            OptimizedHttpFactory::returnToPool($object);
        }
    }

    /**
     * Test stream creation with different content types
     */
    public function testStreamCreationWithDifferentContent(): void
    {
        $contents = [
            '',
            'simple text',
            '{"json": "data"}',
            '<xml>content</xml>',
            str_repeat('large content ', 1000),
        ];

        foreach ($contents as $content) {
            $stream = OptimizedHttpFactory::createStream($content);
            $this->assertInstanceOf(StreamInterface::class, $stream);
            $this->assertEquals($content, (string) $stream);
        }
    }

    /**
     * Test URI creation with different formats
     */
    public function testUriCreationWithDifferentFormats(): void
    {
        $uris = [
            '',
            '/',
            '/path',
            '/path?query=value',
            'http://example.com',
            'https://example.com/path?query=value#fragment',
            'ftp://user:pass@example.com:21/path',
        ];

        foreach ($uris as $uriString) {
            $uri = OptimizedHttpFactory::createUri($uriString);
            $this->assertInstanceOf(UriInterface::class, $uri);
            $this->assertEquals($uriString, (string) $uri);
        }
    }

    /**
     * Test memory efficiency with object pooling
     */
    public function testMemoryEfficiencyWithPooling(): void
    {
        OptimizedHttpFactory::enablePooling();

        $memoryBefore = memory_get_usage();

        // Create and return many objects
        for ($i = 0; $i < 100; $i++) {
            $stream = OptimizedHttpFactory::createStream("test-$i");
            OptimizedHttpFactory::returnToPool($stream);

            $uri = OptimizedHttpFactory::createUri("http://example.com/path-$i");
            OptimizedHttpFactory::returnToPool($uri);
        }

        $memoryAfter = memory_get_usage();
        $memoryIncrease = $memoryAfter - $memoryBefore;

        // Memory increase should be reasonable (less than 10MB for 200 objects)
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease);
    }

    /**
     * Test error handling in stream creation from file
     */
    public function testStreamCreationFromFileErrors(): void
    {
        $this->expectException(\Throwable::class);

        OptimizedHttpFactory::createStreamFromFile('/nonexistent/file.txt');
    }

    /**
     * Test factory behavior with warm-up disabled
     */
    public function testFactoryWithWarmUpDisabled(): void
    {
        OptimizedHttpFactory::initialize(
            [
                'warm_up_pools' => false,
                'enable_pooling' => true,
            ]
        );

        // Should still work without warm-up
        $stream = OptimizedHttpFactory::createStream('test');
        $this->assertInstanceOf(StreamInterface::class, $stream);
    }

    /**
     * Test comprehensive workflow
     */
    public function testComprehensiveWorkflow(): void
    {
        // Initialize with custom config
        OptimizedHttpFactory::initialize(
            [
                'enable_pooling' => true,
                'enable_metrics' => true,
                'max_pool_size' => 50,
            ]
        );

        // Create various HTTP objects
        $request = OptimizedHttpFactory::createRequest('POST', '/api/users', 'handler');
        $response = OptimizedHttpFactory::createResponse();
        $serverRequest = OptimizedHttpFactory::createServerRequest('GET', '/api/status');
        $psr7Response = OptimizedHttpFactory::createPsr7Response(201, ['Location' => '/users/123']);
        $stream = OptimizedHttpFactory::createStream('{"id": 123}');
        $uri = OptimizedHttpFactory::createUri('https://api.example.com/users/123');

        // Verify all objects are created correctly
        $this->assertInstanceOf(Request::class, $request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequest);
        $this->assertInstanceOf(ResponseInterface::class, $psr7Response);
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertInstanceOf(UriInterface::class, $uri);

        // Return some objects to pool
        OptimizedHttpFactory::returnToPool($serverRequest);
        OptimizedHttpFactory::returnToPool($psr7Response);
        OptimizedHttpFactory::returnToPool($stream);
        OptimizedHttpFactory::returnToPool($uri);

        // Get metrics
        $metrics = OptimizedHttpFactory::getPerformanceMetrics();
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('memory_usage', $metrics);

        // Clear pools
        OptimizedHttpFactory::clearPools();

        // Verify factory still works after clearing
        $newStream = OptimizedHttpFactory::createStream('new content');
        $this->assertInstanceOf(StreamInterface::class, $newStream);
    }
}
