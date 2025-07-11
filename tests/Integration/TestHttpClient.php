<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PivotPHP\Core\Core\Application;

/**
 * Test HTTP client for simulating requests
 */
class TestHttpClient
{
    private Application $app;
    private array $requestData = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function request(string $method, string $uri, array $options = []): TestResponse
    {
        try {
            // Store request data for use in executeRouterRequest
            $this->requestData = $options['data'] ?? [];

            // Ensure application is booted
            if (!$this->isAppBooted()) {
                $this->app->boot();
            }

            // Create real request object
            $request = $this->createRealRequest($method, $uri, $options);

            // Create real response object
            $response = $this->createRealResponse();

            // Execute through router
            $router = $this->app->getRouter();
            $result = $this->executeRouterRequest($router, $request, $response, $method, $uri);

            return $this->convertToTestResponse($result);
        } catch (\Exception $e) {
            return new TestResponse(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => $e->getMessage()])
            );
        }
    }

    private function isAppBooted(): bool
    {
        // Check if app is booted using reflection
        try {
            $reflection = new \ReflectionClass($this->app);
            $bootedProperty = $reflection->getProperty('booted');
            $bootedProperty->setAccessible(true);
            return $bootedProperty->getValue($this->app);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function createRealRequest(string $method, string $uri, array $options): object
    {
        try {
            // Use actual PivotPHP Request class with proper constructor arguments
            $request = new \PivotPHP\Core\Http\Request(
                $method,    // method
                $uri,       // path
                $uri        // pathCallable
            );

            // Set additional properties using reflection if needed
            $reflection = new \ReflectionClass($request);

            // Set headers if provided
            if (!empty($options['headers'])) {
                if ($reflection->hasProperty('headers')) {
                    $headersProperty = $reflection->getProperty('headers');
                    $headersProperty->setAccessible(true);
                    $headers = $headersProperty->getValue($request);

                    // If headers is a HeaderRequest object, try to set headers on it
                    if (is_object($headers) && method_exists($headers, 'setHeader')) {
                        foreach ($options['headers'] as $name => $value) {
                            $headers->setHeader($name, $value);
                        }
                    }
                }
            }

            // Set body data if provided
            if (!empty($options['data'])) {
                if ($reflection->hasProperty('body')) {
                    $bodyProperty = $reflection->getProperty('body');
                    $bodyProperty->setAccessible(true);
                    $body = (object) $options['data'];
                    $bodyProperty->setValue($request, $body);
                }
            }

            return $request;
        } catch (\Exception $e) {
            // If construction fails, create a mock request
            return $this->createMockRequest($method, $uri, $options);
        }
    }

    private function createMockRequest(string $method, string $uri, array $options): object
    {
        // Create a simple request object that implements expected interface
        return new class ($method, $uri, $options) {
            private string $method;
            private string $uri;
            private array $options;

            public function __construct(string $method, string $uri, array $options)
            {
                $this->method = strtoupper($method);
                $this->uri = $uri;
                $this->options = $options;
            }

            public function method(): string
            {
                return $this->method;
            }

            public function uri(): string
            {
                return $this->uri;
            }

            public function param(string $key): ?string
            {
                return null;
            }

            public function header(string $key): ?string
            {
                return $this->options['headers'][$key] ?? null;
            }

            public function getBodyAsStdClass(): object
            {
                return (object)($this->options['data'] ?? []);
            }

            public function getMethod(): string
            {
                return $this->method;
            }

            public function getPathCallable(): string
            {
                return $this->uri;
            }

            public function getUri(): object
            {
                return new class ($this->uri) {
                    private string $uri;

                    public function __construct(string $uri)
                    {
                        $this->uri = $uri;
                    }

                    public function getPath(): string
                    {
                        return $this->uri;
                    }

                    public function __toString(): string
                    {
                        return $this->uri;
                    }
                };
            }

            public function getHeaders(): array
            {
                return $this->options['headers'] ?? [];
            }

            public function getBody(): object
            {
                return new class ($this->options['data'] ?? []) {
                    private array $data;

                    public function __construct(array $data)
                    {
                        $this->data = $data;
                    }

                    public function getContents(): string
                    {
                        return json_encode($this->data);
                    }

                    public function __toString(): string
                    {
                        return json_encode($this->data);
                    }
                };
            }
        };
    }

    private function createRealResponse(): object
    {
        // Use actual PivotPHP Response class
        return new \PivotPHP\Core\Http\Response();
    }

    private function executeRouterRequest($router, $request, $response, string $method, string $uri): object
    {
        try {
            // Use the application's handle method for proper middleware execution
            if (method_exists($this->app, 'handle')) {
                // Create a proper request for the application
                $appRequest = $this->createApplicationRequest($method, $uri, $this->requestData ?? []);
                $result = $this->app->handle($appRequest);

                // Convert application response to test response
                return $this->convertApplicationResponse($result);
            }

            // Fallback to direct route execution
            return $this->executeFallbackRoute($router, $request, $response, $method, $uri);
        } catch (\Exception $e) {
            return $response->status(500)->json(['error' => $e->getMessage()]);
        }
    }

    private function createApplicationRequest(string $method, string $uri, array $data = []): object
    {
        // Set up $_SERVER for Request::createFromGlobals()
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_HOST'] = 'localhost';

        // Set up POST data if provided
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $_POST = $data;
        }

        return \PivotPHP\Core\Http\Request::createFromGlobals();
    }

    private function convertApplicationResponse(object $appResponse): object
    {
        // Create a mock response that mimics the application response
        return new class ($appResponse) {
            private object $response;

            public function __construct(object $response)
            {
                $this->response = $response;
            }

            public function getStatusCode(): int
            {
                if (method_exists($this->response, 'getStatusCode')) {
                    return $this->response->getStatusCode();
                }
                return 200;
            }

            public function getHeaders(): array
            {
                if (method_exists($this->response, 'getHeaders')) {
                    return $this->response->getHeaders();
                }
                return [];
            }

            public function getHeader(string $name): ?string
            {
                if (method_exists($this->response, 'getHeader')) {
                    $header = $this->response->getHeader($name);
                    return is_array($header) ? ($header[0] ?? null) : $header;
                }
                return null;
            }

            public function getBody(): string
            {
                if (method_exists($this->response, 'getBody')) {
                    return (string) $this->response->getBody();
                }
                return '';
            }

            public function getJsonData(): array
            {
                return json_decode($this->getBody(), true) ?? [];
            }
        };
    }

    private function executeFallbackRoute($router, $request, $response, string $method, string $uri): object
    {
        // Try to find and execute the route
        $routeFound = false;

        // Get routes from router using reflection
        $reflection = new \ReflectionClass($router);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue($router);

        // Find matching route
        foreach ($routes as $route) {
            if ($this->routeMatches($route, $method, $uri)) {
                $routeFound = true;
                $handler = $route['handler'] ?? null;

                if (is_callable($handler)) {
                    // Execute the handler
                    $result = call_user_func($handler, $request, $response);
                    return $result ?: $response;
                }
            }
        }

        if (!$routeFound) {
            return $response->status(404)->json(['error' => 'Route not found']);
        }

        return $response;
    }

    private function routeMatches(array $route, string $method, string $uri): bool
    {
        // Simple route matching logic
        $routeMethod = $route['method'] ?? '';
        $routePath = $route['path'] ?? '';

        return strtoupper($routeMethod) === strtoupper($method) && $routePath === $uri;
    }

    private function convertToTestResponse(object $response): TestResponse
    {
        try {
            // Try to extract data from actual PivotPHP Response
            $statusCode = 200;
            $headers = [];
            $body = '';

            // Get status code
            if (method_exists($response, 'getStatusCode')) {
                $statusCode = $response->getStatusCode();
            } elseif (method_exists($response, 'status')) {
                // Try to access via reflection
                $reflection = new \ReflectionClass($response);
                if ($reflection->hasProperty('statusCode')) {
                    $prop = $reflection->getProperty('statusCode');
                    $prop->setAccessible(true);
                    $statusCode = $prop->getValue($response);
                }
            }

            // Get headers
            if (method_exists($response, 'getHeaders')) {
                $headers = $response->getHeaders();
            } elseif (method_exists($response, 'headers')) {
                // Try to access via reflection
                $reflection = new \ReflectionClass($response);
                if ($reflection->hasProperty('headers')) {
                    $prop = $reflection->getProperty('headers');
                    $prop->setAccessible(true);
                    $headers = $prop->getValue($response);
                }
            }

            // Get body
            if (method_exists($response, 'getBody')) {
                $bodyObj = $response->getBody();
                $body = (string) $bodyObj;
            } elseif (method_exists($response, 'body')) {
                // Try to access via reflection
                $reflection = new \ReflectionClass($response);
                if ($reflection->hasProperty('body')) {
                    $prop = $reflection->getProperty('body');
                    $prop->setAccessible(true);
                    $body = $prop->getValue($response);
                }
            }

            return new TestResponse($statusCode, $headers, $body);
        } catch (\Exception $e) {
            // Fallback to basic response
            return new TestResponse(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Response conversion failed: ' . $e->getMessage()])
            );
        }
    }

    public function concurrentRequests(array $requests): array
    {
        $results = [];

        // Simulate concurrent execution
        foreach ($requests as $i => $request) {
            $results[$i] = $this->request(
                $request['method'],
                $request['uri'],
                $request['options'] ?? []
            );
        }

        return $results;
    }
}
