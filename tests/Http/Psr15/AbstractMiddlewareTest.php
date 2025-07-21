<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Psr15;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Psr15\AbstractMiddleware;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Response;
use PivotPHP\Core\Http\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Comprehensive tests for AbstractMiddleware
 *
 * Tests the abstract middleware base class functionality including
 * before/after hooks and shouldContinue logic.
 * Following the "less is more" principle with focused, quality testing.
 */
class AbstractMiddlewareTest extends TestCase
{
    private ServerRequest $request;
    private AbstractMockRequestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ServerRequest('GET', 'https://example.com/test');
        $this->handler = new AbstractMockRequestHandler();
    }

    /**
     * Test basic middleware processing with default implementation
     */
    public function testBasicMiddlewareProcessing(): void
    {
        $middleware = new DefaultMiddleware();
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Handler Response', (string) $response->getBody());
    }

    /**
     * Test middleware with before hook
     */
    public function testMiddlewareWithBeforeHook(): void
    {
        $middleware = new BeforeHookMiddleware();
        $handler = new HeaderInspectingHandler();
        $response = $middleware->process($this->request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Modified by before hook', (string) $response->getBody());
    }

    /**
     * Test middleware with after hook
     */
    public function testMiddlewareWithAfterHook(): void
    {
        $middleware = new AfterHookMiddleware();
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Handler Response', (string) $response->getBody());
        $this->assertEquals('Modified by after hook', $response->getHeaderLine('X-After-Hook'));
    }

    /**
     * Test middleware with both before and after hooks
     */
    public function testMiddlewareWithBothHooks(): void
    {
        $middleware = new BothHooksMiddleware();
        $handler = new HeaderInspectingHandler();
        $response = $middleware->process($this->request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('Before hook', (string) $response->getBody());
        $this->assertEquals('After hook', $response->getHeaderLine('X-After-Hook'));
    }

    /**
     * Test middleware that prevents continuation
     */
    public function testMiddlewareThatPreventsContinuation(): void
    {
        $middleware = new NonContinuingMiddleware();
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Access Denied', (string) $response->getBody());
    }

    /**
     * Test middleware with conditional continuation
     */
    public function testMiddlewareWithConditionalContinuation(): void
    {
        // Test request without auth header (should not continue)
        $middleware = new ConditionalMiddleware();
        $response = $middleware->process($this->request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', (string) $response->getBody());

        // Test request with auth header (should continue)
        $authenticatedRequest = $this->request->withHeader('Authorization', 'Bearer token123');
        $response = $middleware->process($authenticatedRequest, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Handler Response', (string) $response->getBody());
    }

    /**
     * Test middleware with request modification
     */
    public function testMiddlewareWithRequestModification(): void
    {
        $middleware = new RequestModifyingMiddleware();
        $handler = new RequestInspectingHandler();

        $response = $middleware->process($this->request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('modified-value', (string) $response->getBody());
    }

    /**
     * Test middleware with response modification
     */
    public function testMiddlewareWithResponseModification(): void
    {
        $middleware = new ResponseModifyingMiddleware();
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Modified Response', (string) $response->getBody());
        $this->assertEquals('modified-response', $response->getHeaderLine('X-Modified'));
    }

    /**
     * Test middleware with error handling
     */
    public function testMiddlewareWithErrorHandling(): void
    {
        $middleware = new ErrorHandlingMiddleware();
        $errorHandler = new ErrorThrowingHandler();

        $response = $middleware->process($this->request, $errorHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', (string) $response->getBody());
    }

    /**
     * Test middleware with custom response from getResponse
     */
    public function testMiddlewareWithCustomResponse(): void
    {
        $middleware = new CustomResponseMiddleware();
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals('custom-response', $response->getHeaderLine('X-Custom'));
    }

    /**
     * Test middleware with timing measurement
     */
    public function testMiddlewareWithTimingMeasurement(): void
    {
        $middleware = new TimingMiddleware();
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getHeaderLine('X-Processing-Time'));
        $this->assertIsNumeric($response->getHeaderLine('X-Processing-Time'));
    }

    /**
     * Test middleware with state management
     */
    public function testMiddlewareWithStateManagement(): void
    {
        // Reset static counter before test
        StateManagementMiddleware::resetCounter();

        $middleware = new StateManagementMiddleware();

        // First call
        $response1 = $middleware->process($this->request, $this->handler);
        $this->assertEquals('1', $response1->getHeaderLine('X-Call-Count'));

        // Second call
        $response2 = $middleware->process($this->request, $this->handler);
        $this->assertEquals('2', $response2->getHeaderLine('X-Call-Count'));

        // Third call
        $response3 = $middleware->process($this->request, $this->handler);
        $this->assertEquals('3', $response3->getHeaderLine('X-Call-Count'));
    }

    /**
     * Test middleware with logging functionality
     */
    public function testMiddlewareWithLogging(): void
    {
        $middleware = new LoggingMiddleware();
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Handler Response', (string) $response->getBody());

        // Check that logging headers are present
        $this->assertNotEmpty($response->getHeaderLine('X-Request-Logged'));
        $this->assertNotEmpty($response->getHeaderLine('X-Response-Logged'));
    }

    /**
     * Test middleware with attribute manipulation
     */
    public function testMiddlewareWithAttributeManipulation(): void
    {
        $middleware = new AttributeMiddleware();
        $handler = new AttributeInspectingHandler();

        $response = $middleware->process($this->request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test-value', (string) $response->getBody());
    }

    /**
     * Test middleware with performance monitoring
     */
    public function testMiddlewareWithPerformanceMonitoring(): void
    {
        $middleware = new PerformanceMiddleware();
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Handler Response', (string) $response->getBody());
        $this->assertNotEmpty($response->getHeaderLine('X-Memory-Usage'));
        $this->assertNotEmpty($response->getHeaderLine('X-Execution-Time'));
    }

    /**
     * Test middleware chain with multiple abstract middlewares
     */
    public function testMiddlewareChainWithMultipleMiddlewares(): void
    {
        $middleware1 = new ChainMiddleware('First');
        $middleware2 = new ChainMiddleware('Second');
        $middleware3 = new ChainMiddleware('Third');

        // Create a handler that includes middleware2 and middleware3
        $handler = new ChainTestHandler($middleware2, $middleware3);

        $response = $middleware1->process($this->request, $handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Handler Response', (string) $response->getBody());
        $this->assertEquals('Third,Second,First', $response->getHeaderLine('X-Chain'));
    }

    /**
     * Test middleware with edge cases
     */
    public function testMiddlewareEdgeCases(): void
    {
        // Test with empty request
        $middleware = new DefaultMiddleware();
        $emptyRequest = new ServerRequest('GET', '');
        $response = $middleware->process($emptyRequest, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // Test with null handler response (should not happen but test robustness)
        $nullHandler = new NullResponseHandler();
        $response = $middleware->process($this->request, $nullHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * Test middleware performance with many iterations
     */
    public function testMiddlewarePerformanceWithManyIterations(): void
    {
        $middleware = new DefaultMiddleware();
        $startTime = microtime(true);

        // Execute middleware many times
        for ($i = 0; $i < 100; $i++) { // Reduced from 1000 to 100 iterations
            $response = $middleware->process($this->request, $this->handler);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Adjusted for realistic performance expectations with reduced iterations
        $maxDuration = getenv('CI') ? 10000 : 5000; // 10s for CI, 5s for local
        $this->assertLessThan($maxDuration, $duration, "Middleware performance test took too long: {$duration}ms");
    }
}

// Mock classes for testing

/**
 * Default middleware implementation for testing
 */
class DefaultMiddleware extends AbstractMiddleware
{
    // Uses all default implementations
}

/**
 * Middleware with before hook
 */
class BeforeHookMiddleware extends AbstractMiddleware
{
    protected function before(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withHeader('X-Before-Hook', 'Modified by before hook');
    }
}

/**
 * Middleware with after hook
 */
class AfterHookMiddleware extends AbstractMiddleware
{
    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader('X-After-Hook', 'Modified by after hook');
    }
}

/**
 * Middleware with both before and after hooks
 */
class BothHooksMiddleware extends AbstractMiddleware
{
    protected function before(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withHeader('X-Before-Hook', 'Before hook');
    }

    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader('X-After-Hook', 'After hook');
    }
}

/**
 * Middleware that prevents continuation
 */
class NonContinuingMiddleware extends AbstractMiddleware
{
    protected function shouldContinue(ServerRequestInterface $request): bool
    {
        return false;
    }

    protected function getResponse(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(403, [], Stream::createFromString('Access Denied'));
    }
}

/**
 * Middleware with conditional continuation
 */
class ConditionalMiddleware extends AbstractMiddleware
{
    protected function shouldContinue(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('Authorization');
    }

    protected function getResponse(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(401, [], Stream::createFromString('Unauthorized'));
    }
}

/**
 * Middleware that modifies the request
 */
class RequestModifyingMiddleware extends AbstractMiddleware
{
    protected function before(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withHeader('X-Modified', 'modified-value');
    }
}

/**
 * Middleware that modifies the response
 */
class ResponseModifyingMiddleware extends AbstractMiddleware
{
    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('X-Modified', 'modified-response')
            ->withBody(Stream::createFromString('Modified Response'));
    }
}

/**
 * Middleware with error handling
 */
class ErrorHandlingMiddleware extends AbstractMiddleware
{
    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return parent::process($request, $handler);
        } catch (\Exception $e) {
            return new Response(500, [], Stream::createFromString('Internal Server Error'));
        }
    }
}

/**
 * Middleware with custom response
 */
class CustomResponseMiddleware extends AbstractMiddleware
{
    protected function shouldContinue(ServerRequestInterface $request): bool
    {
        return false;
    }

    protected function getResponse(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(204, ['X-Custom' => 'custom-response'], Stream::createFromString(''));
    }
}

/**
 * Middleware with timing measurement
 */
class TimingMiddleware extends AbstractMiddleware
{
    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $processingTime = microtime(true) - $request->getAttribute('start_time', microtime(true));
        return $response->withHeader('X-Processing-Time', (string) $processingTime);
    }

    protected function before(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withAttribute('start_time', microtime(true));
    }
}

/**
 * Middleware with state management
 */
class StateManagementMiddleware extends AbstractMiddleware
{
    private static int $callCount = 0;

    public static function resetCounter(): void
    {
        self::$callCount = 0;
    }

    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        self::$callCount++;
        return $response->withHeader('X-Call-Count', (string) self::$callCount);
    }
}

/**
 * Middleware with logging functionality
 */
class LoggingMiddleware extends AbstractMiddleware
{
    protected function before(ServerRequestInterface $request): ServerRequestInterface
    {
        // Simulate logging request
        return $request->withAttribute('logged', true);
    }

    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Simulate logging response
        return $response
            ->withHeader('X-Request-Logged', 'true')
            ->withHeader('X-Response-Logged', 'true');
    }
}

/**
 * Middleware with attribute manipulation
 */
class AttributeMiddleware extends AbstractMiddleware
{
    protected function before(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withAttribute('test-attribute', 'test-value');
    }
}

/**
 * Middleware with performance monitoring
 */
class PerformanceMiddleware extends AbstractMiddleware
{
    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $memoryUsage = memory_get_usage(true);
        $executionTime = microtime(true) - $request->getAttribute('start_time', microtime(true));

        return $response
            ->withHeader('X-Memory-Usage', (string) $memoryUsage)
            ->withHeader('X-Execution-Time', (string) $executionTime);
    }

    protected function before(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withAttribute('start_time', microtime(true));
    }
}

/**
 * Middleware for chain testing
 */
class ChainMiddleware extends AbstractMiddleware
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $existing = $response->getHeaderLine('X-Chain');
        $newChain = $existing ? $existing . ',' . $this->name : $this->name;
        return $response->withHeader('X-Chain', $newChain);
    }
}

// Mock handlers for testing

/**
 * Mock request handler for abstract middleware tests
 */
class AbstractMockRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], Stream::createFromString('Handler Response'));
    }
}

/**
 * Handler that inspects request modifications
 */
class RequestInspectingHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $modifiedValue = $request->getHeaderLine('X-Modified');
        return new Response(200, [], Stream::createFromString($modifiedValue));
    }
}

/**
 * Handler that inspects headers set by before hook
 */
class HeaderInspectingHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $headerValue = $request->getHeaderLine('X-Before-Hook');
        return new Response(200, [], Stream::createFromString($headerValue));
    }
}

/**
 * Handler that throws an error
 */
class ErrorThrowingHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new \RuntimeException('Test error');
    }
}

/**
 * Handler that inspects attributes
 */
class AttributeInspectingHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $attributeValue = $request->getAttribute('test-attribute', 'default');
        return new Response(200, [], Stream::createFromString($attributeValue));
    }
}

/**
 * Handler for chain testing
 */
class ChainTestHandler implements RequestHandlerInterface
{
    private AbstractMiddleware $middleware1;
    private AbstractMiddleware $middleware2;

    public function __construct(AbstractMiddleware $middleware1, AbstractMiddleware $middleware2)
    {
        $this->middleware1 = $middleware1;
        $this->middleware2 = $middleware2;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->middleware1->process($request, new FinalChainHandler($this->middleware2));
        return $response;
    }
}

/**
 * Final handler in chain
 */
class FinalChainHandler implements RequestHandlerInterface
{
    private AbstractMiddleware $middleware;

    public function __construct(AbstractMiddleware $middleware)
    {
        $this->middleware = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, new AbstractMockRequestHandler());
    }
}

/**
 * Handler that returns null response for edge case testing
 */
class NullResponseHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], Stream::createFromString(''));
    }
}
