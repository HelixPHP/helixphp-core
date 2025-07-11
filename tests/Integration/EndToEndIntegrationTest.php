<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

/**
 * End-to-end integration tests covering complete application workflows
 *
 * Tests realistic API scenarios, performance features integration,
 * and full request/response lifecycle.
 *
 * @group integration
 * @group end-to-end
 */
class EndToEndIntegrationTest extends TestCase
{
    private Application $app;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temp directory for testing
        $this->tempDir = sys_get_temp_dir() . '/pivotphp_e2e_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->app = new Application($this->tempDir);

        // Reset performance mode
        HighPerformanceMode::disable();
        OptimizedHttpFactory::disablePooling();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Cleanup
        HighPerformanceMode::disable();
        OptimizedHttpFactory::disablePooling();

        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Test complete REST API workflow
     */
    public function testCompleteRestApiWorkflow(): void
    {
        $this->setupRestApiRoutes();
        $this->app->boot();

        // Simulate in-memory database
        $users = [];
        $nextId = 1;

        // 1. GET /api/users - Empty list
        $response = $this->makeRequest('GET', '/api/users');
        $this->assertEquals(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);
        $this->assertEquals([], $body['users']);

        // 2. POST /api/users - Create user
        $userData = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $response = $this->makeRequest('POST', '/api/users', $userData);
        $this->assertEquals(201, $response->getStatusCode());
        $body = $this->getJsonBody($response);
        $this->assertEquals(1, $body['user']['id']);
        $this->assertEquals('John Doe', $body['user']['name']);

        // Store in "database"
        $users[1] = array_merge(['id' => 1], $userData);

        // 3. GET /api/users/:id - Get specific user
        $response = $this->makeRequest('GET', '/api/users/1');
        $this->assertEquals(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);
        $this->assertEquals(1, $body['user']['id']);
        $this->assertEquals('John Doe', $body['user']['name']);

        // 4. PUT /api/users/:id - Update user
        $updateData = ['name' => 'John Smith', 'email' => 'john.smith@example.com'];
        $response = $this->makeRequest('PUT', '/api/users/1', $updateData);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);
        $this->assertEquals('John Smith', $body['user']['name']);

        // 5. DELETE /api/users/:id - Delete user
        $response = $this->makeRequest('DELETE', '/api/users/1');
        $this->assertEquals(204, $response->getStatusCode());

        // 6. GET /api/users/:id - Verify deletion (404)
        $response = $this->makeRequest('GET', '/api/users/1');
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test high-performance mode integration with real workload
     */
    public function testHighPerformanceModeWithRealWorkload(): void
    {
        // Enable high-performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        OptimizedHttpFactory::enablePooling();

        $this->setupPerformanceRoutes();
        $this->app->boot();

        $startTime = microtime(true);
        $results = [];

        // Simulate concurrent requests
        for ($i = 0; $i < 50; $i++) {
            $endpoint = $i % 3 === 0 ? '/api/fast' : ($i % 3 === 1 ? '/api/medium' : '/api/slow');
            $response = $this->makeRequest('GET', $endpoint);

            $results[] = [
                'status' => $response->getStatusCode(),
                'endpoint' => $endpoint,
                'time' => microtime(true) - $startTime
            ];

            $this->assertEquals(200, $response->getStatusCode());
        }

        $totalTime = microtime(true) - $startTime;
        $throughput = count($results) / $totalTime;

        // Performance assertions
        $this->assertGreaterThan(100, $throughput, 'Should handle >100 req/s in high-performance mode');
        $this->assertLessThan(2.0, $totalTime, 'Should complete 50 requests in <2 seconds');

        // Verify pool usage
        $poolStats = OptimizedHttpFactory::getPoolStats();
        $this->assertGreaterThan(0, $poolStats['usage']['requests_reused']);
    }

    /**
     * Test middleware integration with authentication and authorization
     */
    public function testAuthenticationAndAuthorizationWorkflow(): void
    {
        $this->setupAuthRoutes();
        $this->app->boot();

        // 1. Access protected route without token - 401
        $response = $this->makeRequest('GET', '/api/protected');
        $this->assertEquals(401, $response->getStatusCode());

        // 2. Login to get token
        $loginData = ['username' => 'admin', 'password' => 'secret'];
        $response = $this->makeRequest('POST', '/api/login', $loginData);
        $this->assertEquals(200, $response->getStatusCode());

        $body = $this->getJsonBody($response);
        $token = $body['token'];
        $this->assertNotEmpty($token);

        // 3. Access protected route with token - 200
        $response = $this->makeRequest(
            'GET',
            '/api/protected',
            null,
            [
                'Authorization' => 'Bearer ' . $token
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());

        // 4. Access admin route with user token - 403
        $response = $this->makeRequest(
            'GET',
            '/api/admin',
            null,
            [
                'Authorization' => 'Bearer ' . $token
            ]
        );
        $this->assertEquals(403, $response->getStatusCode());

        // 5. Login as admin
        $adminLogin = ['username' => 'superuser', 'password' => 'supersecret'];
        $response = $this->makeRequest('POST', '/api/login', $adminLogin);
        $adminBody = $this->getJsonBody($response);
        $adminToken = $adminBody['token'];

        // 6. Access admin route with admin token - 200
        $response = $this->makeRequest(
            'GET',
            '/api/admin',
            null,
            [
                'Authorization' => 'Bearer ' . $adminToken
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test error handling and recovery scenarios
     */
    public function testErrorHandlingAndRecoveryScenarios(): void
    {
        $this->setupErrorHandlingRoutes();
        $this->app->boot();

        // 1. 404 for non-existent route
        $response = $this->makeRequest('GET', '/non-existent');
        $this->assertEquals(404, $response->getStatusCode());

        // 2. 400 for validation error
        $response = $this->makeRequest('POST', '/api/validate', ['invalid' => 'data']);
        $this->assertEquals(400, $response->getStatusCode());
        $body = $this->getJsonBody($response);
        $this->assertArrayHasKey('errors', $body);

        // 3. 500 for server error
        $response = $this->makeRequest('GET', '/api/error');
        $this->assertEquals(500, $response->getStatusCode());

        // 4. Rate limiting
        for ($i = 0; $i < 12; $i++) {
            $response = $this->makeRequest('GET', '/api/limited');

            if ($i < 10) {
                $this->assertEquals(200, $response->getStatusCode());
            } else {
                $this->assertEquals(429, $response->getStatusCode());
            }
        }
    }

    /**
     * Test content negotiation and multiple formats
     */
    public function testContentNegotiationAndFormats(): void
    {
        $this->setupContentNegotiationRoutes();
        $this->app->boot();

        $testData = ['message' => 'Hello World', 'timestamp' => time()];

        // 1. JSON response (default)
        $response = $this->makeRequest('GET', '/api/data');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        // 2. JSON with explicit Accept header
        $response = $this->makeRequest(
            'GET',
            '/api/data',
            null,
            [
                'Accept' => 'application/json'
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $body = $this->getJsonBody($response);
        $this->assertArrayHasKey('message', $body);

        // 3. Text response
        $response = $this->makeRequest(
            'GET',
            '/api/data',
            null,
            [
                'Accept' => 'text/plain'
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/plain', $response->getHeaderLine('Content-Type'));

        // 4. XML response (if implemented)
        $response = $this->makeRequest(
            'GET',
            '/api/data',
            null,
            [
                'Accept' => 'application/xml'
            ]
        );

        // May return 406 if XML not supported, or 200 if it is
        $this->assertContains($response->getStatusCode(), [200, 406]);
    }

    /**
     * Test streaming and large response handling
     */
    public function testStreamingAndLargeResponseHandling(): void
    {
        $this->setupStreamingRoutes();
        $this->app->boot();

        // 1. Small response
        $response = $this->makeRequest('GET', '/api/data/small');
        $this->assertEquals(200, $response->getStatusCode());

        $body = $this->getJsonBody($response);
        $this->assertCount(10, $body['items']);

        // 2. Medium response
        $response = $this->makeRequest('GET', '/api/data/medium');
        $this->assertEquals(200, $response->getStatusCode());

        $body = $this->getJsonBody($response);
        $this->assertCount(100, $body['items']);

        // 3. Large response (test memory efficiency)
        $memoryBefore = memory_get_usage();

        $response = $this->makeRequest('GET', '/api/data/large');
        $this->assertEquals(200, $response->getStatusCode());

        $memoryAfter = memory_get_usage();
        $memoryIncrease = $memoryAfter - $memoryBefore;

        // Should not use excessive memory
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory usage should be reasonable');
    }

    /**
     * Setup REST API routes for testing
     */
    private function setupRestApiRoutes(): void
    {
        $users = [];
        $nextId = 1;

        $this->app->get(
            '/api/users',
            function ($req, $res) use (&$users) {
                return $res->json(['users' => array_values($users)]);
            }
        );

        $this->app->get(
            '/api/users/:id',
            function ($req, $res) use (&$users) {
                $id = (int)$req->param('id');
                if (!isset($users[$id])) {
                    return $res->status(404)->json(['error' => 'User not found']);
                }
                return $res->json(['user' => $users[$id]]);
            }
        );

        $this->app->post(
            '/api/users',
            function ($req, $res) use (&$users, &$nextId) {
                $body = $req->getBodyAsStdClass();
                $user = ['id' => $nextId++, 'name' => $body->name ?? '', 'email' => $body->email ?? ''];
                $users[$user['id']] = $user;
                return $res->status(201)->json(['user' => $user]);
            }
        );

        $this->app->put(
            '/api/users/:id',
            function ($req, $res) use (&$users) {
                $id = (int)$req->param('id');
                if (!isset($users[$id])) {
                    return $res->status(404)->json(['error' => 'User not found']);
                }
                $body = $req->getBodyAsStdClass();
                $users[$id]['name'] = $body->name ?? $users[$id]['name'];
                $users[$id]['email'] = $body->email ?? $users[$id]['email'];
                return $res->json(['user' => $users[$id]]);
            }
        );

        $this->app->delete(
            '/api/users/:id',
            function ($req, $res) use (&$users) {
                $id = (int)$req->param('id');
                unset($users[$id]);
                return $res->status(204);
            }
        );
    }

    /**
     * Setup performance testing routes
     */
    private function setupPerformanceRoutes(): void
    {
        $this->app->get(
            '/api/fast',
            function ($req, $res) {
                return $res->json(['data' => 'fast response', 'timestamp' => microtime(true)]);
            }
        );

        $this->app->get(
            '/api/medium',
            function ($req, $res) {
                usleep(1000); // 1ms delay
                return $res->json(['data' => 'medium response', 'timestamp' => microtime(true)]);
            }
        );

        $this->app->get(
            '/api/slow',
            function ($req, $res) {
                usleep(5000); // 5ms delay
                return $res->json(['data' => 'slow response', 'timestamp' => microtime(true)]);
            }
        );
    }

    /**
     * Setup authentication routes
     */
    private function setupAuthRoutes(): void
    {
        // Simple auth middleware
        $this->app->use(
            function ($req, $res, $next) {
                $path = $req->getPathCallable();

                if (strpos($path, '/api/protected') === 0 || strpos($path, '/api/admin') === 0) {
                    $auth = $req->header('Authorization');
                    if (!$auth || !preg_match('/Bearer\s+(.+)/', $auth, $matches)) {
                        return $res->status(401)->json(['error' => 'Unauthorized']);
                    }

                    $token = $matches[1];
                    $decoded = base64_decode($token);
                    $userData = json_decode($decoded, true);

                    if (!$userData) {
                        return $res->status(401)->json(['error' => 'Invalid token']);
                    }

                    // Store user data in request for later use
                    $reflection = new \ReflectionClass($req);
                    if ($reflection->hasProperty('attributes')) {
                        $attrProperty = $reflection->getProperty('attributes');
                        $attrProperty->setAccessible(true);
                        $attributes = $attrProperty->getValue($req) ?? [];
                        $attributes['user'] = $userData;
                        $attrProperty->setValue($req, $attributes);
                    }

                    if (strpos($path, '/api/admin') === 0 && $userData['role'] !== 'admin') {
                        return $res->status(403)->json(['error' => 'Admin access required']);
                    }
                }

                return $next($req, $res);
            }
        );

        $this->app->post(
            '/api/login',
            function ($req, $res) {
                $body = $req->getBodyAsStdClass();
                $username = $body->username ?? '';
                $password = $body->password ?? '';

                $users = [
                    'admin' => ['password' => 'secret', 'role' => 'admin'],
                    'superuser' => ['password' => 'supersecret', 'role' => 'admin']
                ];

                if (!isset($users[$username]) || $users[$username]['password'] !== $password) {
                    return $res->status(401)->json(['error' => 'Invalid credentials']);
                }

                $userData = ['username' => $username, 'role' => $users[$username]['role']];
                $token = base64_encode(json_encode($userData));

                return $res->json(['token' => $token, 'user' => $userData]);
            }
        );

        $this->app->get(
            '/api/protected',
            function ($req, $res) {
                $user = $req->getAttribute('user');
                return $res->json(['message' => 'Protected resource', 'user' => $user]);
            }
        );

        $this->app->get(
            '/api/admin',
            function ($req, $res) {
                $user = $req->getAttribute('user');
                return $res->json(['message' => 'Admin resource', 'user' => $user]);
            }
        );
    }

    /**
     * Setup error handling routes
     */
    private function setupErrorHandlingRoutes(): void
    {
        $requestCount = 0;

        $this->app->post(
            '/api/validate',
            function ($req, $res) {
                $body = $req->getBodyAsStdClass();
                $errors = [];

                if (!isset($body->name) || empty($body->name)) {
                    $errors[] = 'Name is required';
                }

                if (!isset($body->email) || !filter_var($body->email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Valid email is required';
                }

                if (!empty($errors)) {
                    return $res->status(400)->json(['errors' => $errors]);
                }

                return $res->json(['message' => 'Validation passed']);
            }
        );

        $this->app->get(
            '/api/error',
            function ($req, $res) {
                throw new \Exception('Intentional server error');
            }
        );

        $this->app->get(
            '/api/limited',
            function ($req, $res) use (&$requestCount) {
                $requestCount++;

                if ($requestCount > 10) {
                    return $res->status(429)->json(['error' => 'Rate limit exceeded']);
                }

                return $res->json(['message' => 'Request allowed', 'count' => $requestCount]);
            }
        );
    }

    /**
     * Setup content negotiation routes
     */
    private function setupContentNegotiationRoutes(): void
    {
        $this->app->get(
            '/api/data',
            function ($req, $res) {
                $data = ['message' => 'Hello World', 'timestamp' => time()];
                $accept = $req->header('Accept') ?? 'application/json';

                if (strpos($accept, 'application/json') !== false) {
                    return $res->json($data);
                } elseif (strpos($accept, 'text/plain') !== false) {
                    return $res->text($data['message']);
                } elseif (strpos($accept, 'application/xml') !== false) {
                    return $res->status(406)->json(['error' => 'XML not supported']);
                }

                return $res->json($data);
            }
        );
    }

    /**
     * Setup streaming routes
     */
    private function setupStreamingRoutes(): void
    {
        $this->app->get(
            '/api/data/small',
            function ($req, $res) {
                $items = [];
                for ($i = 0; $i < 10; $i++) {
                    $items[] = ['id' => $i, 'value' => "item_$i"];
                }
                return $res->json(['items' => $items]);
            }
        );

        $this->app->get(
            '/api/data/medium',
            function ($req, $res) {
                $items = [];
                for ($i = 0; $i < 100; $i++) {
                    $items[] = ['id' => $i, 'value' => "item_$i"];
                }
                return $res->json(['items' => $items]);
            }
        );

        $this->app->get(
            '/api/data/large',
            function ($req, $res) {
                $items = [];
                for ($i = 0; $i < 1000; $i++) {
                    $items[] = ['id' => $i, 'value' => "item_$i", 'data' => str_repeat('x', 100)];
                }
                return $res->json(['items' => $items]);
            }
        );
    }

    /**
     * Helper to make HTTP requests
     */
    private function makeRequest(string $method, string $path, ?array $data = null, array $headers = []): Response
    {
        // Set up $_SERVER for proper request creation
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['HTTP_HOST'] = 'localhost';

        // Set headers
        foreach ($headers as $name => $value) {
            $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($name))] = $value;
        }

        // Set body for POST/PUT requests
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $_POST = $data;
            $_SERVER['CONTENT_TYPE'] = 'application/json';
        }

        $request = Request::createFromGlobals();

        // Manually set body if needed
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $reflection = new \ReflectionClass($request);
            if ($reflection->hasProperty('body')) {
                $bodyProperty = $reflection->getProperty('body');
                $bodyProperty->setAccessible(true);
                $bodyProperty->setValue($request, (object) $data);
            }
        }

        return $this->app->handle($request);
    }

    /**
     * Helper to get JSON body from response
     */
    private function getJsonBody(Response $response): array
    {
        $body = $response->getBody()->__toString();
        return json_decode($body, true) ?? [];
    }
}
