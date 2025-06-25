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
    }

    public function testJWTAuthenticationSuccess(): void
    {
        // Arrange
        $payload = ['user_id' => 1, 'username' => 'testuser'];
        $token = JWTHelper::encode($payload, $this->jwtSecret);
        
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
        
        $request = (object) ['headers' => (object) ['authorization' => "Bearer $token"]];
        $response = (object) [];
        $nextCalled = false;
        
        $middleware = AuthMiddleware::jwt($this->jwtSecret);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertObjectHasAttribute('user', $request);
        $this->assertObjectHasAttribute('auth', $request);
        $this->assertEquals('jwt', $request->auth['method']);
        $this->assertEquals(1, $request->user['user_id']);
    }

    public function testJWTAuthenticationFailure(): void
    {
        // Arrange
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer invalid_token";
        
        $request = (object) ['headers' => (object) ['authorization' => "Bearer invalid_token"]];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $middleware = AuthMiddleware::jwt($this->jwtSecret);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertFalse($nextCalled);
        $this->assertEquals(401, $response->statusCode);
    }

    public function testBasicAuthenticationSuccess(): void
    {
        // Arrange
        $credentials = base64_encode('admin:password123');
        $_SERVER['HTTP_AUTHORIZATION'] = "Basic $credentials";
        
        $request = (object) ['headers' => (object) ['authorization' => "Basic $credentials"]];
        $response = (object) [];
        $nextCalled = false;
        
        $callback = function($username, $password) {
            if ($username === 'admin' && $password === 'password123') {
                return ['id' => 1, 'username' => 'admin', 'role' => 'admin'];
            }
            return false;
        };
        
        $middleware = AuthMiddleware::basic($callback);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertObjectHasAttribute('user', $request);
        $this->assertEquals('basic', $request->auth['method']);
        $this->assertEquals('admin', $request->user['username']);
    }

    public function testBasicAuthenticationFailure(): void
    {
        // Arrange
        $credentials = base64_encode('admin:wrongpassword');
        $_SERVER['HTTP_AUTHORIZATION'] = "Basic $credentials";
        
        $request = (object) ['headers' => (object) ['authorization' => "Basic $credentials"]];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $callback = function($username, $password) {
            return false; // Always fail
        };
        
        $middleware = AuthMiddleware::basic($callback);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertFalse($nextCalled);
        $this->assertEquals(401, $response->statusCode);
    }

    public function testApiKeyAuthenticationViaHeader(): void
    {
        // Arrange
        $_SERVER['HTTP_X_API_KEY'] = 'valid_key_123';
        
        $request = (object) ['headers' => (object) []];
        $response = (object) [];
        $nextCalled = false;
        
        $callback = function($apiKey) {
            if ($apiKey === 'valid_key_123') {
                return ['id' => 1, 'name' => 'API Client', 'permissions' => ['read']];
            }
            return false;
        };
        
        $middleware = AuthMiddleware::apiKey($callback);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertObjectHasAttribute('user', $request);
        $this->assertEquals('apikey', $request->auth['method']);
        $this->assertEquals('API Client', $request->user['name']);
    }

    public function testApiKeyAuthenticationViaQuery(): void
    {
        // Arrange
        $_GET['api_key'] = 'valid_key_456';
        
        $request = (object) [
            'headers' => (object) [],
            'query' => function($param) {
                return $_GET[$param] ?? null;
            }
        ];
        $response = (object) [];
        $nextCalled = false;
        
        $callback = function($apiKey) {
            if ($apiKey === 'valid_key_456') {
                return ['id' => 2, 'name' => 'Mobile App'];
            }
            return false;
        };
        
        $middleware = AuthMiddleware::apiKey($callback);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertEquals('Mobile App', $request->user['name']);
    }

    public function testBearerTokenAuthentication(): void
    {
        // Arrange
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer custom_token_123";
        
        $request = (object) ['headers' => (object) ['authorization' => "Bearer custom_token_123"]];
        $response = (object) [];
        $nextCalled = false;
        
        $callback = function($token) {
            if ($token === 'custom_token_123') {
                return ['id' => 1, 'service' => 'external_api'];
            }
            return false;
        };
        
        $middleware = AuthMiddleware::bearer($callback);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertEquals('bearer', $request->auth['method']);
        $this->assertEquals('external_api', $request->user['service']);
    }

    public function testMultipleAuthMethods(): void
    {
        // Arrange - JWT token
        $payload = ['user_id' => 1, 'username' => 'jwtuser'];
        $token = JWTHelper::encode($payload, $this->jwtSecret);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
        
        $request = (object) ['headers' => (object) ['authorization' => "Bearer $token"]];
        $response = (object) [];
        $nextCalled = false;
        
        $middleware = new AuthMiddleware([
            'authMethods' => ['jwt', 'basic', 'apikey'],
            'jwtSecret' => $this->jwtSecret,
            'basicAuthCallback' => function() { return false; },
            'apiKeyCallback' => function() { return false; },
            'allowMultiple' => true
        ]);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertEquals('jwt', $request->auth['method']);
        $this->assertEquals('jwtuser', $request->user['username']);
    }

    public function testExcludedPaths(): void
    {
        // Arrange
        $request = (object) ['path' => '/public/status'];
        $response = (object) [];
        $nextCalled = false;
        
        $middleware = new AuthMiddleware([
            'authMethods' => ['jwt'],
            'jwtSecret' => $this->jwtSecret,
            'excludePaths' => ['/public']
        ]);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertObjectNotHasAttribute('user', $request);
    }

    public function testFlexibleModeWithoutAuth(): void
    {
        // Arrange
        $request = (object) ['headers' => (object) []];
        $response = (object) [];
        $nextCalled = false;
        
        $middleware = AuthMiddleware::flexible([
            'authMethods' => ['jwt'],
            'jwtSecret' => $this->jwtSecret
        ]);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertObjectNotHasAttribute('user', $request);
    }

    public function testCustomAuthentication(): void
    {
        // Arrange
        $_COOKIE['session_id'] = 'valid_session_123';
        
        $request = (object) ['headers' => (object) []];
        $response = (object) [];
        $nextCalled = false;
        
        $customCallback = function($req) {
            $sessionId = $_COOKIE['session_id'] ?? null;
            if ($sessionId === 'valid_session_123') {
                return ['id' => 1, 'username' => 'session_user'];
            }
            return false;
        };
        
        $middleware = AuthMiddleware::custom($customCallback);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertEquals('custom', $request->auth['method']);
        $this->assertEquals('session_user', $request->user['username']);
    }

    public function testCustomUserProperty(): void
    {
        // Arrange
        $payload = ['user_id' => 1, 'username' => 'testuser'];
        $token = JWTHelper::encode($payload, $this->jwtSecret);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
        
        $request = (object) ['headers' => (object) ['authorization' => "Bearer $token"]];
        $response = (object) [];
        $nextCalled = false;
        
        $middleware = new AuthMiddleware([
            'authMethods' => ['jwt'],
            'jwtSecret' => $this->jwtSecret,
            'userProperty' => 'currentUser'
        ]);
        
        // Act
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        // Assert
        $this->assertTrue($nextCalled);
        $this->assertObjectHasAttribute('currentUser', $request);
        $this->assertObjectNotHasAttribute('user', $request);
        $this->assertEquals('testuser', $request->currentUser['username']);
    }

    private function createMockResponse()
    {
        return new class {
            public $statusCode;
            public $jsonData;
            
            public function status($code) {
                $this->statusCode = $code;
                return $this;
            }
            
            public function json($data) {
                $this->jsonData = $data;
                return $this;
            }
        };
    }
}
