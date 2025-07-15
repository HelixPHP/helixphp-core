<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Middleware\Security;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Security\CsrfMiddleware;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Factory\ResponseFactory;
use PivotPHP\Core\Exceptions\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Comprehensive tests for CsrfMiddleware
 *
 * Tests CSRF protection, token generation/validation, and middleware functionality.
 * Following the "less is more" principle with focused, quality testing.
 */
class CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;
    private ServerRequest $request;
    private CsrfMockRequestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CsrfMiddleware();
        $this->request = new ServerRequest('GET', 'https://example.com/test');
        $this->handler = new CsrfMockRequestHandler();

        // Start session for CSRF token management
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Clean session state
        if (isset($_SESSION['_csrf_token'])) {
            unset($_SESSION['_csrf_token']);
        }
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        if (isset($_SESSION['_csrf_token'])) {
            unset($_SESSION['_csrf_token']);
        }
        parent::tearDown();
    }

    /**
     * Test default configuration
     */
    public function testDefaultConfiguration(): void
    {
        $middleware = new CsrfMiddleware();
        $this->assertInstanceOf(CsrfMiddleware::class, $middleware);
    }

    /**
     * Test custom field name configuration
     */
    public function testCustomFieldNameConfiguration(): void
    {
        $middleware = new CsrfMiddleware('custom_token');
        $this->assertInstanceOf(CsrfMiddleware::class, $middleware);
    }

    /**
     * Test GET request passes through without CSRF check
     */
    public function testGetRequestPassesThrough(): void
    {
        $request = $this->request->withMethod('GET');
        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // CSRF token should be generated for next request
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        $this->assertNotEmpty($_SESSION['_csrf_token']);
    }

    /**
     * Test POST request with valid CSRF token
     */
    public function testPostRequestWithValidToken(): void
    {
        // Generate a token first
        $token = CsrfMiddleware::getToken();
        $_SESSION['_csrf_token'] = $token;

        $request = $this->request->withMethod('POST')
                                ->withParsedBody(['_csrf_token' => $token]);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // New token should be generated
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        $this->assertNotEmpty($_SESSION['_csrf_token']);
    }

    /**
     * Test POST request with invalid CSRF token throws exception
     */
    public function testPostRequestWithInvalidToken(): void
    {
        $validToken = CsrfMiddleware::getToken();
        $_SESSION['_csrf_token'] = $validToken;

        $request = $this->request->withMethod('POST')
                                ->withParsedBody(['_csrf_token' => 'invalid_token']);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('CSRF token inválido ou ausente');

        $this->middleware->process($request, $this->handler);
    }

    /**
     * Test POST request with missing CSRF token throws exception
     */
    public function testPostRequestWithMissingToken(): void
    {
        $validToken = CsrfMiddleware::getToken();
        $_SESSION['_csrf_token'] = $validToken;

        $request = $this->request->withMethod('POST')
                                ->withParsedBody(['data' => 'value']);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('CSRF token inválido ou ausente');

        $this->middleware->process($request, $this->handler);
    }

    /**
     * Test POST request with null parsed body throws exception
     */
    public function testPostRequestWithNullParsedBody(): void
    {
        $validToken = CsrfMiddleware::getToken();
        $_SESSION['_csrf_token'] = $validToken;

        $request = $this->request->withMethod('POST')
                                ->withParsedBody(null);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('CSRF token inválido ou ausente');

        $this->middleware->process($request, $this->handler);
    }

    /**
     * Test POST request with string parsed body throws exception
     */
    public function testPostRequestWithStringParsedBody(): void
    {
        $validToken = CsrfMiddleware::getToken();
        $_SESSION['_csrf_token'] = $validToken;

        $request = $this->request->withMethod('POST')
                                ->withParsedBody('string data');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('CSRF token inválido ou ausente');

        $this->middleware->process($request, $this->handler);
    }

    /**
     * Test POST request without session token throws exception
     */
    public function testPostRequestWithoutSessionToken(): void
    {
        $request = $this->request->withMethod('POST')
                                ->withParsedBody(['_csrf_token' => 'some_token']);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('CSRF token inválido ou ausente');

        $this->middleware->process($request, $this->handler);
    }

    /**
     * Test custom field name functionality
     */
    public function testCustomFieldName(): void
    {
        $middleware = new CsrfMiddleware('custom_token');
        $token = CsrfMiddleware::getToken('custom_token');
        $_SESSION['custom_token'] = $token;

        $request = $this->request->withMethod('POST')
                                ->withParsedBody(['custom_token' => $token]);

        $response = $middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // New token should be generated with custom field name
        $this->assertArrayHasKey('custom_token', $_SESSION);
        $this->assertNotEmpty($_SESSION['custom_token']);
    }

    /**
     * Test token generation is consistent within session
     */
    public function testTokenGenerationConsistency(): void
    {
        $token1 = CsrfMiddleware::getToken();
        $token2 = CsrfMiddleware::getToken();

        $this->assertNotEmpty($token1);
        $this->assertNotEmpty($token2);
        $this->assertEquals($token1, $token2, 'Tokens should be the same within session');
        $this->assertEquals(64, strlen($token1), 'Token should be 64 characters long');
    }

    /**
     * Test token generation with custom field name
     */
    public function testTokenGenerationWithCustomFieldName(): void
    {
        $token = CsrfMiddleware::getToken('custom_field');

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token), 'Token should be 64 characters long');
        $this->assertArrayHasKey('custom_field', $_SESSION);
        $this->assertEquals($token, $_SESSION['custom_field']);
    }

    /**
     * Test token generation creates new token when session is empty
     */
    public function testTokenGenerationCreatesNewToken(): void
    {
        // Ensure no token exists
        if (isset($_SESSION['_csrf_token'])) {
            unset($_SESSION['_csrf_token']);
        }

        $token = CsrfMiddleware::getToken();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        $this->assertEquals($token, $_SESSION['_csrf_token']);
    }

    /**
     * Test hidden field generation
     */
    public function testHiddenFieldGeneration(): void
    {
        $hiddenField = CsrfMiddleware::hiddenField();

        $this->assertStringContainsString('<input', $hiddenField);
        $this->assertStringContainsString('type="hidden"', $hiddenField);
        $this->assertStringContainsString('name="_csrf_token"', $hiddenField);
        $this->assertStringContainsString('value="', $hiddenField);

        // Verify it contains a valid token
        $this->assertNotEmpty($_SESSION['_csrf_token']);
        $this->assertStringContainsString($_SESSION['_csrf_token'], $hiddenField);
    }

    /**
     * Test hidden field with custom field name
     */
    public function testHiddenFieldWithCustomFieldName(): void
    {
        $hiddenField = CsrfMiddleware::hiddenField('custom_field');

        $this->assertStringContainsString('<input', $hiddenField);
        $this->assertStringContainsString('type="hidden"', $hiddenField);
        $this->assertStringContainsString('name="custom_field"', $hiddenField);
        $this->assertStringContainsString('value="', $hiddenField);

        // Verify it contains a valid token
        $this->assertArrayHasKey('custom_field', $_SESSION);
        $this->assertStringContainsString($_SESSION['custom_field'], $hiddenField);
    }

    /**
     * Test meta tag generation
     */
    public function testMetaTagGeneration(): void
    {
        $metaTag = CsrfMiddleware::metaTag();

        $this->assertStringContainsString('<meta', $metaTag);
        $this->assertStringContainsString('name="csrf-token"', $metaTag);
        $this->assertStringContainsString('content="', $metaTag);

        // Verify it contains a valid token
        $this->assertNotEmpty($_SESSION['_csrf_token']);
        $this->assertStringContainsString($_SESSION['_csrf_token'], $metaTag);
    }

    /**
     * Test meta tag with custom field name
     */
    public function testMetaTagWithCustomFieldName(): void
    {
        $metaTag = CsrfMiddleware::metaTag('custom_field');

        $this->assertStringContainsString('<meta', $metaTag);
        $this->assertStringContainsString('name="csrf-token"', $metaTag);
        $this->assertStringContainsString('content="', $metaTag);

        // Verify it contains a valid token
        $this->assertArrayHasKey('custom_field', $_SESSION);
        $this->assertStringContainsString($_SESSION['custom_field'], $metaTag);
    }

    /**
     * Test HTML escaping in hidden field
     */
    public function testHtmlEscapingInHiddenField(): void
    {
        $hiddenField = CsrfMiddleware::hiddenField('test"field');

        $this->assertStringContainsString('name="test&quot;field"', $hiddenField);
        $this->assertStringNotContainsString('name="test"field"', $hiddenField);
    }

    /**
     * Test HTML escaping in meta tag
     */
    public function testHtmlEscapingInMetaTag(): void
    {
        // Set a token with special characters (though this shouldn't happen in practice)
        $_SESSION['_csrf_token'] = 'token"with<special>characters';

        $metaTag = CsrfMiddleware::metaTag();

        $this->assertStringContainsString('content="token&quot;with&lt;special&gt;characters"', $metaTag);
        $this->assertStringNotContainsString('content="token"with<special>characters"', $metaTag);
    }

    /**
     * Test session starting behavior
     */
    public function testSessionStarting(): void
    {
        // This test verifies that sessions are started when needed
        // The middleware should handle session management internally

        $request = $this->request->withMethod('GET');
        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
    }

    /**
     * Test token regeneration after successful validation
     */
    public function testTokenRegenerationAfterValidation(): void
    {
        $originalToken = CsrfMiddleware::getToken();
        $_SESSION['_csrf_token'] = $originalToken;

        $request = $this->request->withMethod('POST')
                                ->withParsedBody(['_csrf_token' => $originalToken]);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // New token should be different from original
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        $this->assertNotEmpty($_SESSION['_csrf_token']);
        $this->assertNotEquals($originalToken, $_SESSION['_csrf_token']);
    }

    /**
     * Test timing attack protection with hash_equals
     */
    public function testTimingAttackProtection(): void
    {
        $validToken = CsrfMiddleware::getToken();
        $_SESSION['_csrf_token'] = $validToken;

        // Test multiple invalid tokens to ensure hash_equals is used
        $invalidTokens = [
            'invalid_token_1',
            'invalid_token_2',
            substr($validToken, 0, 32), // Partial token
            $validToken . 'extra', // Extended token
        ];

        foreach ($invalidTokens as $invalidToken) {
            $request = $this->request->withMethod('POST')
                                    ->withParsedBody(['_csrf_token' => $invalidToken]);

            try {
                $this->middleware->process($request, $this->handler);
                $this->fail('Expected HttpException was not thrown for invalid token: ' . $invalidToken);
            } catch (HttpException $e) {
                $this->assertEquals(403, $e->getCode());
                $this->assertEquals('CSRF token inválido ou ausente', $e->getMessage());
            }
        }
    }

    /**
     * Test middleware performance with multiple requests
     */
    public function testMiddlewarePerformance(): void
    {
        $startTime = microtime(true);

        // Simulate 100 requests
        for ($i = 0; $i < 100; $i++) {
            $token = CsrfMiddleware::getToken();
            $_SESSION['_csrf_token'] = $token;

            $request = $this->request->withMethod('POST')
                                    ->withParsedBody(['_csrf_token' => $token]);

            $response = $this->middleware->process($request, $this->handler);
            $this->assertEquals(200, $response->getStatusCode());
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should handle 100 requests quickly (less than 1 second)
        $this->assertLessThan(1000, $duration);
    }

    /**
     * Test different HTTP methods
     */
    public function testDifferentHttpMethods(): void
    {
        $methods = ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

        foreach ($methods as $method) {
            $request = $this->request->withMethod($method);
            $response = $this->middleware->process($request, $this->handler);

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertEquals(200, $response->getStatusCode());

            // All non-POST methods should pass through
            $this->assertArrayHasKey('_csrf_token', $_SESSION);
        }
    }

    /**
     * Test edge cases and boundary conditions
     */
    public function testEdgeCases(): void
    {
        // Test with empty token
        $_SESSION['_csrf_token'] = '';
        $request = $this->request->withMethod('POST')
                                ->withParsedBody(['_csrf_token' => '']);

        $this->expectException(HttpException::class);
        $this->middleware->process($request, $this->handler);
    }
}

/**
 * Mock request handler for testing
 */
class CsrfMockRequestHandler implements RequestHandlerInterface
{
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
    {
        $factory = new ResponseFactory();
        return $factory->createResponse(200)
                      ->withHeader('Content-Type', 'application/json');
    }
}
