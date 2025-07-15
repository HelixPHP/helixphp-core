<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Exceptions\Enhanced;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;
use PivotPHP\Core\Core\Environment;

class ContextualExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        Environment::clearCache();
    }

    protected function tearDown(): void
    {
        Environment::clearCache();
    }

    public function testBasicContextualException(): void
    {
        $context = ['user_id' => 123, 'action' => 'update'];
        $suggestions = ['Check user permissions', 'Verify data format'];

        $exception = new ContextualException(
            400,
            'Validation failed',
            $context,
            $suggestions,
            'VALIDATION'
        );

        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals($suggestions, $exception->getSuggestions());
        $this->assertEquals('VALIDATION', $exception->getCategory());
    }

    public function testDebugInfoInDevelopmentMode(): void
    {
        $_ENV['APP_ENV'] = 'development';
        Environment::clearCache();

        $exception = new ContextualException(
            500,
            'Test error',
            ['test' => 'value'],
            ['Test suggestion'],
            'TEST'
        );

        $debugInfo = $exception->getDebugInfo();
        $this->assertStringContainsString('STACK TRACE:', $debugInfo);
        $this->assertStringContainsString('ERROR: Test error', $debugInfo);
        $this->assertStringContainsString('STATUS: 500', $debugInfo);
        $this->assertStringContainsString('CATEGORY: TEST', $debugInfo);
    }

    public function testDebugInfoInProductionMode(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['APP_DEBUG'] = 'false';

        // Store and disable display_errors to ensure production detection
        $originalDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');

        Environment::clearCache();

        try {
            $exception = new ContextualException(
                500,
                'Test error',
                ['test' => 'value'],
                ['Test suggestion'],
                'TEST'
            );

            $debugInfo = $exception->getDebugInfo();
            $this->assertStringNotContainsString('STACK TRACE:', $debugInfo);
            $this->assertStringContainsString('ERROR: Test error', $debugInfo);
        } finally {
            ini_set('display_errors', $originalDisplayErrors);
        }
    }

    public function testToArrayInDevelopmentMode(): void
    {
        $_ENV['APP_ENV'] = 'development';
        Environment::clearCache();

        $exception = new ContextualException(
            400,
            'Test error',
            ['key' => 'value'],
            ['Test suggestion'],
            'TEST'
        );

        $array = $exception->toArray();

        $this->assertTrue($array['error']);
        $this->assertEquals(400, $array['status']);
        $this->assertEquals('Test error', $array['message']);
        $this->assertEquals('TEST', $array['category']);
        $this->assertEquals(['key' => 'value'], $array['context']);
        $this->assertEquals(['Test suggestion'], $array['suggestions']);
        $this->assertArrayHasKey('debug', $array);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('line', $array);
    }

    public function testToArrayInProductionMode(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['APP_DEBUG'] = 'false';

        // Store and disable display_errors to ensure production detection
        $originalDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');

        Environment::clearCache();

        try {
            $exception = new ContextualException(
                400,
                'Test error',
                ['key' => 'value'],
                ['Test suggestion'],
                'TEST'
            );

            $array = $exception->toArray();

            $this->assertTrue($array['error']);
            $this->assertEquals(400, $array['status']);
            $this->assertEquals('Test error', $array['message']);
            $this->assertEquals('TEST', $array['category']);
            $this->assertArrayNotHasKey('context', $array);
            $this->assertArrayNotHasKey('suggestions', $array);
            $this->assertArrayNotHasKey('debug', $array);
            $this->assertArrayNotHasKey('file', $array);
            $this->assertArrayNotHasKey('line', $array);
        } finally {
            ini_set('display_errors', $originalDisplayErrors);
        }
    }

    public function testRouteNotFoundFactory(): void
    {
        $exception = ContextualException::routeNotFound(
            'GET',
            '/api/users/123',
            ['/api/users', '/api/auth']
        );

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertStringContainsString('Route not found: GET /api/users/123', $exception->getMessage());
        $this->assertEquals('ROUTING', $exception->getCategory());

        $context = $exception->getContext();
        $this->assertEquals('GET', $context['method']);
        $this->assertEquals('/api/users/123', $context['path']);
        $this->assertEquals(['/api/users', '/api/auth'], $context['available_routes']);
    }

    public function testHandlerErrorFactory(): void
    {
        $exception = ContextualException::handlerError(
            'array_callable',
            'Method does not exist',
            ['class' => 'TestController', 'method' => 'nonexistent']
        );

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('Handler execution failed: Method does not exist', $exception->getMessage());
        $this->assertEquals('HANDLER', $exception->getCategory());

        $context = $exception->getContext();
        $this->assertEquals('array_callable', $context['handler_type']);
        $this->assertEquals('Method does not exist', $context['error']);
    }

    public function testParameterErrorFactory(): void
    {
        $exception = ContextualException::parameterError(
            'id',
            'integer',
            'abc',
            '/users/:id'
        );

        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertStringContainsString(
            "Parameter validation failed for 'id': expected integer",
            $exception->getMessage()
        );
        $this->assertEquals('PARAMETER', $exception->getCategory());

        $context = $exception->getContext();
        $this->assertEquals('id', $context['parameter']);
        $this->assertEquals('integer', $context['expected_type']);
        $this->assertEquals('abc', $context['actual_value']);
        $this->assertEquals('string', $context['actual_type']);
    }

    public function testMiddlewareErrorFactory(): void
    {
        $exception = ContextualException::middlewareError(
            'AuthMiddleware',
            'Token validation failed',
            ['AuthMiddleware', 'CorsMiddleware']
        );

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString(
            "Middleware 'AuthMiddleware' failed: Token validation failed",
            $exception->getMessage()
        );
        $this->assertEquals('MIDDLEWARE', $exception->getCategory());

        $context = $exception->getContext();
        $this->assertEquals('AuthMiddleware', $context['middleware']);
        $this->assertEquals('Token validation failed', $context['error']);
        $this->assertEquals(['AuthMiddleware', 'CorsMiddleware'], $context['middleware_stack']);
    }
}
