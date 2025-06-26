<?php

namespace Express\Tests\Security;

use PHPUnit\Framework\TestCase;
use Express\Middlewares\Security\SecurityMiddleware;

class SecurityMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset global state
        $_SERVER = [];
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    public function testSecurityMiddlewareInitialization(): void
    {
        $middleware = new SecurityMiddleware();
        $this->assertInstanceOf(SecurityMiddleware::class, $middleware);
    }

    public function testSecurityMiddlewareWithDefaultOptions(): void
    {
        $middleware = new SecurityMiddleware();
        
        $request = (object) ['method' => 'GET'];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        $this->assertTrue($nextCalled);
    }

    public function testSecurityMiddlewareWithCustomOptions(): void
    {
        $options = [
            'enableCsrf' => false,
            'enableXss' => false,
            'sessionSecurity' => false,
            'rateLimiting' => false
        ];
        
        $middleware = new SecurityMiddleware($options);
        
        $request = (object) ['method' => 'GET'];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        $this->assertTrue($nextCalled);
    }

    public function testSecurityMiddlewareWithCsrfEnabled(): void
    {
        $options = [
            'enableCsrf' => true,
            'enableXss' => false,
            'sessionSecurity' => false
        ];
        
        $middleware = new SecurityMiddleware($options);
        
        $request = (object) ['method' => 'GET'];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        $this->assertTrue($nextCalled);
    }

    public function testSecurityMiddlewareWithXssEnabled(): void
    {
        $options = [
            'enableCsrf' => false,
            'enableXss' => true,
            'sessionSecurity' => false
        ];
        
        $middleware = new SecurityMiddleware($options);
        
        $request = (object) ['method' => 'GET'];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        $this->assertTrue($nextCalled);
    }

    public function testSecurityMiddlewareWithAllFeaturesEnabled(): void
    {
        $options = [
            'enableCsrf' => true,
            'enableXss' => true,
            'sessionSecurity' => true,
            'rateLimiting' => false
        ];
        
        $middleware = new SecurityMiddleware($options);
        
        $request = (object) ['method' => 'GET'];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        $this->assertTrue($nextCalled);
    }

    public function testSecurityHeadersAreSet(): void
    {
        $middleware = new SecurityMiddleware([
            'enableCsrf' => false,
            'enableXss' => false,
            'sessionSecurity' => false
        ]);
        
        $request = (object) ['method' => 'GET'];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        $this->assertTrue($nextCalled);
        
        // Check if security headers are set
        $headers = $response->headers;
        $this->assertGreaterThan(0, count($headers));
    }

    public function testMiddlewareChaining(): void
    {
        $middleware = new SecurityMiddleware();
        
        $request = (object) ['method' => 'POST'];
        $response = $this->createMockResponse();
        
        $middlewareStack = [
            function($req, $res, $next) {
                $req->test1 = true;
                $next();
            },
            $middleware,
            function($req, $res, $next) {
                $req->test2 = true;
                $next();
            }
        ];
        
        $this->executeMiddlewareStack($middlewareStack, $request, $response);
        
        $this->assertTrue($request->test1);
        $this->assertTrue($request->test2);
    }

    public function testSecurityWithDifferentHttpMethods(): void
    {
        $middleware = new SecurityMiddleware();
        
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        
        foreach ($methods as $method) {
            $request = (object) ['method' => $method];
            $response = $this->createMockResponse();
            $nextCalled = false;
            
            $middleware($request, $response, function() use (&$nextCalled) {
                $nextCalled = true;
            });
            
            $this->assertTrue($nextCalled, "Failed for method: $method");
        }
    }

    public function testSecurityMiddlewareWithCustomCsrfOptions(): void
    {
        $options = [
            'enableCsrf' => true,
            'csrf' => [
                'tokenName' => 'custom_token',
                'excludeMethods' => ['GET', 'HEAD', 'OPTIONS']
            ],
            'enableXss' => false,
            'sessionSecurity' => false
        ];
        
        $middleware = new SecurityMiddleware($options);
        
        $request = (object) ['method' => 'GET'];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        $this->assertTrue($nextCalled);
    }

    public function testSecurityMiddlewareWithCustomXssOptions(): void
    {
        $options = [
            'enableCsrf' => false,
            'enableXss' => true,
            'xss' => [
                'allowedTags' => '<p><br>',
                'encoding' => 'UTF-8'
            ],
            'sessionSecurity' => false
        ];
        
        $middleware = new SecurityMiddleware($options);
        
        $request = (object) ['method' => 'POST'];
        $response = $this->createMockResponse();
        $nextCalled = false;
        
        $middleware($request, $response, function() use (&$nextCalled) {
            $nextCalled = true;
        });
        
        $this->assertTrue($nextCalled);
    }

    /**
     * Create a mock response object for testing
     */
    private function createMockResponse()
    {
        return new class {
            public $headers = [];
            public $status = [];
            
            public function header($name, $value) {
                $this->headers[] = "$name: $value";
                return $this;
            }
            
            public function status($code) {
                $this->status[] = "Status: $code";
                return $this;
            }
            
            public function json($data) {
                return $this;
            }
            
            public function text($text) {
                return $this;
            }
            
            public function end() {
                return $this;
            }
        };
    }

    /**
     * Execute a middleware stack for testing
     */
    private function executeMiddlewareStack($middlewares, $request, $response)
    {
        $index = 0;
        $next = function() use (&$index, $middlewares, $request, $response, &$next) {
            if ($index < count($middlewares)) {
                $middleware = $middlewares[$index++];
                $middleware($request, $response, $next);
            }
        };
        $next();
    }
}
