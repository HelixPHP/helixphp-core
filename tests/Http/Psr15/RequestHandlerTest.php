<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Psr15;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Psr15\RequestHandler;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Response;
use PivotPHP\Core\Http\Psr7\Stream;
use PivotPHP\Core\Http\Psr7\Factory\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Comprehensive tests for PSR-15 RequestHandler
 *
 * Tests PSR-15 request handler and middleware stack processing.
 * Following the "less is more" principle with focused, quality testing.
 */
class RequestHandlerTest extends TestCase
{
    private RequestHandler $handler;
    private ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new RequestHandler();
        $this->request = new ServerRequest('GET', 'https://example.com/test');
    }

    /**
     * Test basic request handler creation
     */
    public function testBasicRequestHandlerCreation(): void
    {
        $handler = new RequestHandler();
        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
    }

    /**
     * Test request handler with fallback handler
     */
    public function testRequestHandlerWithFallbackHandler(): void
    {
        $fallbackHandler = new MockRequestHandler();
        $handler = new RequestHandler($fallbackHandler);
        
        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
    }

    /**
     * Test handling request with no middleware returns 404
     */
    public function testHandleRequestWithNoMiddleware(): void
    {
        $response = $this->handler->handle($this->request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', (string) $response->getBody());
    }

    /**
     * Test handling request with fallback handler
     */
    public function testHandleRequestWithFallbackHandler(): void
    {
        $fallbackHandler = new MockRequestHandler();
        $handler = new RequestHandler($fallbackHandler);
        
        $response = $handler->handle($this->request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Mock Response', (string) $response->getBody());
    }

    /**
     * Test adding middleware to handler
     */
    public function testAddingMiddleware(): void
    {
        $middleware = new MockMiddleware();
        $result = $this->handler->add($middleware);
        
        $this->assertSame($this->handler, $result);
    }

    /**
     * Test handling request with single middleware
     */
    public function testHandleRequestWithSingleMiddleware(): void
    {
        $middleware = new MockMiddleware('Modified by middleware');
        $this->handler->add($middleware);
        
        $response = $this->handler->handle($this->request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Modified by middleware', (string) $response->getBody());
    }

    /**
     * Test handling request with multiple middlewares
     */
    public function testHandleRequestWithMultipleMiddlewares(): void
    {
        $middleware1 = new MockMiddleware('First middleware');
        $middleware2 = new MockMiddleware('Second middleware');
        
        $this->handler->add($middleware1)->add($middleware2);
        
        $response = $this->handler->handle($this->request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('First middleware', (string) $response->getBody());
    }

    /**
     * Test middleware execution order
     */
    public function testMiddlewareExecutionOrder(): void
    {
        $middleware1 = new RHOrderTrackingMiddleware('First');
        $middleware2 = new RHOrderTrackingMiddleware('Second');
        $middleware3 = new RHOrderTrackingMiddleware('Third');
        
        $this->handler->add($middleware1)
                     ->add($middleware2)
                     ->add($middleware3);
        
        $response = $this->handler->handle($this->request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(404, $response->getStatusCode()); // No fallback handler, so 404
        
        // Since no fallback handler, we won't have the execution order header
        // Let's test with a fallback handler
        $fallbackHandler = new MockRequestHandler();
        $handler = new RequestHandler($fallbackHandler);
        
        $handler->add($middleware1)
                ->add($middleware2)
                ->add($middleware3);
        
        $response = $handler->handle($this->request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Third,Second,First', $response->getHeaderLine('X-Execution-Order'));
    }

    /**
     * Test middleware with fallback handler
     */
    public function testMiddlewareWithFallbackHandler(): void
    {
        $fallbackHandler = new MockRequestHandler();
        $handler = new RequestHandler($fallbackHandler);
        
        $middleware = new RHPassThroughMiddleware();
        $handler->add($middleware);
        
        $response = $handler->handle($this->request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Mock Response', (string) $response->getBody());
    }

    /**
     * Test middleware that terminates the chain
     */
    public function testMiddlewareThatTerminatesChain(): void
    {
        $terminatingMiddleware = new RHTerminatingMiddleware();
        $normalMiddleware = new MockMiddleware('Should not be reached');
        
        $this->handler->add($terminatingMiddleware)
                     ->add($normalMiddleware);
        
        $response = $this->handler->handle($this->request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Terminated', (string) $response->getBody());
    }

    /**
     * Test handler reset functionality
     */
    public function testHandlerReset(): void
    {
        $middleware = new MockMiddleware('Test response');
        $this->handler->add($middleware);
        
        // First call
        $response1 = $this->handler->handle($this->request);
        $this->assertEquals(200, $response1->getStatusCode());
        
        // Reset handler
        $result = $this->handler->reset();
        $this->assertSame($this->handler, $result);
        
        // Second call after reset
        $response2 = $this->handler->handle($this->request);
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /**
     * Test middleware stack with request modification
     */
    public function testMiddlewareStackWithRequestModification(): void
    {
        $middleware1 = new RequestHandlerRequestModifyingMiddleware('X-Modified-By', 'Middleware1');
        $middleware2 = new RequestHandlerRequestModifyingMiddleware('X-Modified-By', 'Middleware2');
        $middleware3 = new RHRequestReadingMiddleware();
        
        $this->handler->add($middleware1)
                     ->add($middleware2)
                     ->add($middleware3);
        
        $response = $this->handler->handle($this->request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Middleware2', (string) $response->getBody());
    }

    /**
     * Test performance with many middlewares
     */
    public function testPerformanceWithManyMiddlewares(): void
    {
        // Add many middlewares
        for ($i = 0; $i < 50; $i++) {
            $middleware = new RHPassThroughMiddleware();
            $this->handler->add($middleware);
        }
        
        $fallbackHandler = new MockRequestHandler();
        $handler = new RequestHandler($fallbackHandler);
        
        for ($i = 0; $i < 50; $i++) {
            $middleware = new RHPassThroughMiddleware();
            $handler->add($middleware);
        }
        
        $startTime = microtime(true);
        $response = $handler->handle($this->request);
        $endTime = microtime(true);
        
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(50, $duration); // Should be fast (less than 50ms)
    }

    /**
     * Test middleware exception handling
     */
    public function testMiddlewareExceptionHandling(): void
    {
        $middleware = new RHExceptionThrowingMiddleware();
        $this->handler->add($middleware);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test exception');
        
        $this->handler->handle($this->request);
    }

    /**
     * Test complex middleware interaction
     */
    public function testComplexMiddlewareInteraction(): void
    {
        $authMiddleware = new RHAuthenticationMiddleware();
        $loggingMiddleware = new RHLoggingMiddleware();
        $validationMiddleware = new RHValidationMiddleware();
        
        // Need fallback handler for proper response
        $fallbackHandler = new MockRequestHandler();
        $handler = new RequestHandler($fallbackHandler);
        
        $handler->add($authMiddleware)
                ->add($loggingMiddleware)
                ->add($validationMiddleware);
        
        $response = $handler->handle($this->request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Validated,Logged,Authenticated', $response->getHeaderLine('X-Processed-By'));
    }

    /**
     * Test edge cases
     */
    public function testEdgeCases(): void
    {
        // Test with empty middleware stack and fallback
        $fallbackHandler = new MockRequestHandler();
        $handler = new RequestHandler($fallbackHandler);
        
        $response = $handler->handle($this->request);
        $this->assertEquals(200, $response->getStatusCode());
        
        // Test reset on empty handler
        $emptyHandler = new RequestHandler();
        $result = $emptyHandler->reset();
        $this->assertSame($emptyHandler, $result);
    }
}

/**
 * Mock middleware for testing
 */
class MockMiddleware implements MiddlewareInterface
{
    private string $responseBody;

    public function __construct(string $responseBody = 'Mock Response')
    {
        $this->responseBody = $responseBody;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new Response(200, [], Stream::createFromString($this->responseBody));
    }
}

/**
 * Mock request handler for testing
 */
class MockRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], Stream::createFromString('Mock Response'));
    }
}

/**
 * Pass-through middleware for testing
 */
class RHPassThroughMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}

/**
 * Middleware that terminates the chain
 */
class RHTerminatingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new Response(403, [], Stream::createFromString('Terminated'));
    }
}

/**
 * Middleware that tracks execution order
 */
class RHOrderTrackingMiddleware implements MiddlewareInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        
        $existing = $response->getHeaderLine('X-Execution-Order');
        $newOrder = $existing ? $existing . ',' . $this->name : $this->name;
        
        return $response->withHeader('X-Execution-Order', $newOrder);
    }
}

/**
 * Middleware that modifies request for RequestHandler tests
 */
class RequestHandlerRequestModifyingMiddleware implements MiddlewareInterface
{
    private string $headerName;
    private string $headerValue;

    public function __construct(string $headerName, string $headerValue)
    {
        $this->headerName = $headerName;
        $this->headerValue = $headerValue;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $modifiedRequest = $request->withHeader($this->headerName, $this->headerValue);
        return $handler->handle($modifiedRequest);
    }
}

/**
 * Middleware that reads from request
 */
class RHRequestReadingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $headerValue = $request->getHeaderLine('X-Modified-By');
        return new Response(200, [], Stream::createFromString($headerValue));
    }
}

/**
 * Middleware that throws exception
 */
class RHExceptionThrowingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        throw new \RuntimeException('Test exception');
    }
}

/**
 * Authentication middleware for testing
 */
class RHAuthenticationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        
        $existing = $response->getHeaderLine('X-Processed-By');
        $newValue = $existing ? $existing . ',Authenticated' : 'Authenticated';
        
        return $response->withHeader('X-Processed-By', $newValue);
    }
}

/**
 * Logging middleware for testing
 */
class RHLoggingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        
        $existing = $response->getHeaderLine('X-Processed-By');
        $newValue = $existing ? $existing . ',Logged' : 'Logged';
        
        return $response->withHeader('X-Processed-By', $newValue);
    }
}

/**
 * Validation middleware for testing
 */
class RHValidationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        
        $existing = $response->getHeaderLine('X-Processed-By');
        $newValue = $existing ? $existing . ',Validated' : 'Validated';
        
        return $response->withHeader('X-Processed-By', $newValue);
    }
}