<?php

namespace Express\Tests\Security;

use PHPUnit\Framework\TestCase;
use Express\Middlewares\Security\AuthMiddleware;
use Express\Helpers\JWTHelper;

class AuthMiddlewareTest extends TestCase
{
    private $jwtSecret = 'test_secret_key_123';

    protected function setUp(): void
    {
        // Reset global state
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];

        // Simular ambiente web mínimo
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';
    }

    private function createMockRequest($headers = [], $query = []): object
    {
        return (object) [
            'headers' => (object) $headers,
            'query' => (object) $query,
            'method' => 'GET',
            'path' => '/test'
        ];
    }

    private function createMockResponse(): object
    {
        return new class {
            public $statusCode = 200;
            public $headers = [];
            public $body = '';

            public function status($code) {
                $this->statusCode = $code;
                return $this;
            }

            public function json($data) {
                $this->body = json_encode($data);
                return $this;
            }

            public function send($data) {
                $this->body = $data;
                return $this;
            }
        };
    }

    public function testJWTAuthenticationSuccess(): void
    {
        // Arrange
        $payload = ['user_id' => 1, 'username' => 'testuser'];
        $token = JWTHelper::encode($payload, $this->jwtSecret);

        $request = $this->createMockRequest(['authorization' => "Bearer $token"]);
        $response = $this->createMockResponse();
        $nextCalled = false;

        $middleware = AuthMiddleware::jwt($this->jwtSecret);

        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });

        // Assert
        $this->assertTrue($nextCalled);
        $this->assertTrue(property_exists($request, 'user'));
    }

    public function testJWTAuthenticationFailure(): void
    {
        $request = $this->createMockRequest(['authorization' => 'Bearer invalid_token']);
        $response = $this->createMockResponse();
        $nextCalled = false;

        $middleware = AuthMiddleware::jwt($this->jwtSecret);

        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });

        // Com token inválido, deve falhar na autenticação
        $this->assertFalse($nextCalled);
        $this->assertEquals(401, $response->statusCode);
    }

    public function testBasicAuthenticationSuccess(): void
    {
        $validCredentials = ['testuser' => 'testpass'];

        $request = $this->createMockRequest(['authorization' => 'Basic ' . base64_encode('testuser:testpass')]);
        $response = $this->createMockResponse();
        $nextCalled = false;

        $middleware = AuthMiddleware::basic($validCredentials);

        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });

        $this->assertTrue($nextCalled);
        $this->assertTrue(property_exists($request, 'user'));
    }

    public function testBasicAuthenticationFailure(): void
    {
        $validCredentials = ['testuser' => 'testpass'];

        $request = $this->createMockRequest(['authorization' => 'Basic ' . base64_encode('testuser:wrongpass')]);
        $response = $this->createMockResponse();
        $nextCalled = false;

        $middleware = AuthMiddleware::basic($validCredentials);

        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });

        $this->assertFalse($nextCalled);
        $this->assertEquals(401, $response->statusCode);
    }

    public function testBearerTokenAuthentication(): void
    {
        $validTokens = ['valid_token_123'];

        $request = $this->createMockRequest(['authorization' => 'Bearer valid_token_123']);
        $response = $this->createMockResponse();
        $nextCalled = false;

        $middleware = AuthMiddleware::bearer($validTokens);

        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });

        $this->assertTrue($nextCalled);
        $this->assertTrue(property_exists($request, 'user'));
    }

    public function testCustomAuthentication(): void
    {
        $customAuth = function($request) {
            if (($request->headers->customAuth ?? '') === 'valid') {
                return ['id' => 1, 'name' => 'custom_user'];
            }
            return false;
        };

        $request = $this->createMockRequest(['customAuth' => 'valid']);
        $response = $this->createMockResponse();
        $nextCalled = false;

        $middleware = AuthMiddleware::custom($customAuth);

        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });

        $this->assertTrue($nextCalled);
        $this->assertTrue(property_exists($request, 'user'));
    }
}
