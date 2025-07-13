<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Http;

use PivotPHP\Core\Tests\Integration\IntegrationTestCase;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Performance\HighPerformanceMode;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Layer Integration Tests
 *
 * Tests the integration between:
 * - Request/Response objects
 * - PSR-7 compliance in real scenarios
 * - Headers and body handling
 * - Content negotiation
 * - File uploads
 * - HTTP methods and status codes
 *
 * @group integration
 * @group http
 * @group http-layer
 */
class HttpLayerIntegrationTest extends IntegrationTestCase
{
    /**
     * Test basic HTTP request/response cycle
     */
    public function testBasicHttpRequestResponseCycle(): void
    {
        // Register route that exercises request/response
        $this->app->get(
            '/http-test',
            function ($req, $res) {
                return $res->status(200)
                      ->header('X-Test-Header', 'integration-test')
                    ->json(
                        [
                            'method' => $req->getMethod(),
                            'path' => $req->getPathCallable(),
                            'headers_count' => count($req->getHeaders()),
                            'user_agent' => $req->userAgent(),
                            'is_secure' => $req->isSecure()
                        ]
                    );
            }
        );

        // Execute request
        $response = $this->simulateRequest(
            'GET',
            '/http-test',
            [],
            [
                'User-Agent' => 'PivotPHP-Test/1.0',
                'Accept' => 'application/json'
            ]
        );

        // Validate response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type'));
        $this->assertEquals('integration-test', $response->getHeader('X-Test-Header'));

        $data = $response->getJsonData();
        $this->assertEquals('GET', $data['method']);
        $this->assertEquals('/http-test', $data['path']);
        $this->assertIsInt($data['headers_count']);
        $this->assertIsBool($data['is_secure']);
    }

    /**
     * Test PSR-7 compliance in real middleware scenarios
     */
    public function testPsr7ComplianceInMiddleware(): void
    {
        // Add PSR-7 middleware
        $this->app->use(
            function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            // Test PSR-7 request methods
                $method = $request->getMethod();
                $uri = $request->getUri();
                $headers = $request->getHeaders();

            // Add PSR-7 attribute
                $request = $request->withAttribute('psr7_processed', true);
                $request = $request->withAttribute('original_method', $method);

                return $next($request, $response);
            }
        );

        // Add route that uses PSR-7 attributes
        $this->app->post(
            '/psr7-test',
            function ($req, $res) {
                return $res->json(
                    [
                        'psr7_processed' => $req->getAttribute('psr7_processed'),
                        'original_method' => $req->getAttribute('original_method'),
                        'uri_path' => (string) $req->getUri(),
                        'protocol_version' => $req->getProtocolVersion(),
                        'has_content_type' => $req->hasHeader('Content-Type')
                    ]
                );
            }
        );

        // Execute POST request
        $response = $this->simulateRequest(
            'POST',
            '/psr7-test',
            ['test_data' => 'psr7_integration'],
            ['Content-Type' => 'application/json']
        );

        // Validate PSR-7 compliance
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getJsonData();
        $this->assertTrue($data['psr7_processed']);
        $this->assertEquals('POST', $data['original_method']);
        $this->assertStringContainsString('/psr7-test', $data['uri_path']);
        $this->assertIsString($data['protocol_version']);
        $this->assertIsBool($data['has_content_type']);
    }

    /**
     * Test comprehensive headers handling
     */
    public function testComprehensiveHeadersHandling(): void
    {
        // Route that manipulates various headers
        $this->app->get(
            '/headers-test',
            function ($req, $res) {
                return $res->status(200)
                      ->header('X-Custom-Header', 'custom-value')
                      ->header('X-Request-ID', uniqid('req_'))
                      ->header('Cache-Control', 'no-cache, must-revalidate')
                      ->header('X-Response-Time', (string) microtime(true))
                    ->json(
                        [
                            'received_headers' => [
                                'accept' => $req->header('Accept'),
                                'user_agent' => $req->header('User-Agent'),
                                'authorization' => $req->header('Authorization'),
                                'x_custom' => $req->header('X-Custom-Request')
                            ],
                            'headers_via_psr7' => $req->getHeaders(),
                            'header_line_accept' => $req->getHeaderLine('Accept')
                        ]
                    );
            }
        );

        // Execute with multiple headers
        $response = $this->simulateRequest(
            'GET',
            '/headers-test',
            [],
            [
                'Accept' => 'application/json,text/html;q=0.9',
                'User-Agent' => 'PivotPHP-Integration-Test/1.0',
                'Authorization' => 'Bearer test-token-123',
                'X-Custom-Request' => 'integration-test-value'
            ]
        );

        // Validate headers
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('custom-value', $response->getHeader('X-Custom-Header'));
        $this->assertNotEmpty($response->getHeader('X-Request-ID'));
        $this->assertEquals('no-cache, must-revalidate', $response->getHeader('Cache-Control'));

        $data = $response->getJsonData();

        // Test header retrieval methods
        $this->assertIsArray($data['received_headers']);
        $this->assertIsArray($data['headers_via_psr7']);
        $this->assertIsString($data['header_line_accept']);
    }

    /**
     * Test request body handling with different content types
     */
    public function testRequestBodyHandling(): void
    {
        // JSON body route
        $this->app->post(
            '/json-body',
            function ($req, $res) {
                $body = $req->getBodyAsStdClass();
                $parsedBody = $req->getParsedBody();

                return $res->json(
                    [
                        'body_type' => gettype($body),
                        'body_properties' => get_object_vars($body),
                        'parsed_body_type' => gettype($parsedBody),
                        'input_method' => $req->input('name', 'not_found'),
                        'body_size' => strlen((string) $req->getBody())
                    ]
                );
            }
        );

        // Form data route
        $this->app->post(
            '/form-body',
            function ($req, $res) {
                return $res->json(
                    [
                        'form_data' => (array) $req->getBodyAsStdClass(),
                        'input_email' => $req->input('email', 'not_provided'),
                        'all_inputs' => (array) $req->getBodyAsStdClass()
                    ]
                );
            }
        );

        // Test JSON body
        $jsonResponse = $this->simulateRequest(
            'POST',
            '/json-body',
            [
                'name' => 'Integration Test',
                'type' => 'http_layer_test',
                'nested' => ['data' => 'value']
            ],
            ['Content-Type' => 'application/json']
        );

        $this->assertEquals(200, $jsonResponse->getStatusCode());
        $jsonData = $jsonResponse->getJsonData();
        $this->assertEquals('object', $jsonData['body_type']);
        $this->assertEquals('Integration Test', $jsonData['input_method']);
        $this->assertIsArray($jsonData['body_properties']);

        // Test form data
        $formResponse = $this->simulateRequest(
            'POST',
            '/form-body',
            [
                'email' => 'test@example.com',
                'username' => 'testuser'
            ],
            ['Content-Type' => 'application/x-www-form-urlencoded']
        );

        $this->assertEquals(200, $formResponse->getStatusCode());
        $formData = $formResponse->getJsonData();
        $this->assertEquals('test@example.com', $formData['input_email']);
        $this->assertIsArray($formData['all_inputs']);
    }

    /**
     * Test different HTTP methods integration
     */
    public function testHttpMethodsIntegration(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $route = "/method-test-" . strtolower($method);

            // Register route for each method
            $this->app->{strtolower($method)}(
                $route,
                function ($req, $res) {
                    return $res->json(
                        [
                            'method' => $req->getMethod(),
                            'route_executed' => true,
                            'timestamp' => time(),
                            'request_target' => $req->getRequestTarget()
                        ]
                    );
                }
            );
        }

        // Test each method
        foreach ($methods as $method) {
            $route = "/method-test-" . strtolower($method);
            $response = $this->simulateRequest($method, $route);

            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Method {$method} failed"
            );

            $data = $response->getJsonData();
            $this->assertEquals(
                $method,
                $data['method'],
                "Method mismatch for {$method}"
            );
            $this->assertTrue(
                $data['route_executed'],
                "Route not executed for {$method}"
            );
        }
    }

    /**
     * Test response content types and serialization
     */
    public function testResponseContentTypesAndSerialization(): void
    {
        // JSON response
        $this->app->get(
            '/json-response',
            function ($req, $res) {
                return $res->json(
                    [
                        'message' => 'JSON response test',
                        'data' => ['key' => 'value'],
                        'timestamp' => time()
                    ]
                );
            }
        );

        // Text response
        $this->app->get(
            '/text-response',
            function ($req, $res) {
                return $res->status(200)
                      ->header('Content-Type', 'text/plain')
                      ->send('Plain text response for integration testing');
            }
        );

        // HTML response
        $this->app->get(
            '/html-response',
            function ($req, $res) {
                $html = '<html><body><h1>Integration Test</h1><p>HTML response</p></body></html>';
                return $res->status(200)
                      ->header('Content-Type', 'text/html')
                      ->send($html);
            }
        );

        // Test JSON response
        $jsonResponse = $this->simulateRequest('GET', '/json-response');
        $this->assertEquals(200, $jsonResponse->getStatusCode());
        $this->assertStringContainsString('application/json', $jsonResponse->getHeader('Content-Type'));

        $jsonData = $jsonResponse->getJsonData();
        $this->assertEquals('JSON response test', $jsonData['message']);
        $this->assertIsArray($jsonData['data']);

        // Test text response
        $textResponse = $this->simulateRequest('GET', '/text-response');
        $this->assertEquals(200, $textResponse->getStatusCode());
        $this->assertStringContainsString('text/plain', $textResponse->getHeader('Content-Type'));
        $this->assertStringContainsString('Plain text response', $textResponse->getBody());

        // Test HTML response
        $htmlResponse = $this->simulateRequest('GET', '/html-response');
        $this->assertEquals(200, $htmlResponse->getStatusCode());
        $this->assertStringContainsString('text/html', $htmlResponse->getHeader('Content-Type'));
        $this->assertStringContainsString('<h1>Integration Test</h1>', $htmlResponse->getBody());
    }

    /**
     * Test status codes and error responses
     */
    public function testStatusCodesAndErrorResponses(): void
    {
        $statusTests = [
            ['code' => 200, 'route' => '/status-200', 'message' => 'OK'],
            ['code' => 201, 'route' => '/status-201', 'message' => 'Created'],
            ['code' => 400, 'route' => '/status-400', 'message' => 'Bad Request'],
            ['code' => 401, 'route' => '/status-401', 'message' => 'Unauthorized'],
            ['code' => 404, 'route' => '/status-404', 'message' => 'Not Found'],
            ['code' => 500, 'route' => '/status-500', 'message' => 'Internal Server Error']
        ];

        foreach ($statusTests as $test) {
            $this->app->get(
                $test['route'],
                function ($req, $res) use ($test) {
                    return $res->status($test['code'])->json(
                        [
                            'status' => $test['code'],
                            'message' => $test['message'],
                            'test' => 'status_integration'
                        ]
                    );
                }
            );
        }

        // Test each status code
        foreach ($statusTests as $test) {
            $response = $this->simulateRequest('GET', $test['route']);

            $this->assertEquals(
                $test['code'],
                $response->getStatusCode(),
                "Status code mismatch for {$test['code']}"
            );

            $data = $response->getJsonData();
            $this->assertEquals($test['code'], $data['status']);
            $this->assertEquals($test['message'], $data['message']);
        }
    }

    /**
     * Test request parameter extraction
     */
    public function testRequestParameterExtraction(): void
    {
        // Route with parameters
        $this->app->get(
            '/users/:id/posts/:postId',
            function ($req, $res) {
                return $res->json(
                    [
                        'user_id' => $req->param('id'),
                        'post_id' => $req->param('postId'),
                        'user_id_type' => gettype($req->param('id')),
                        'all_params' => (array) $req->getParams(),
                        'query_page' => $req->get('page', '1'),
                        'query_limit' => $req->get('limit', '10')
                    ]
                );
            }
        );

        // Execute with parameters (no query string in URL for now)
        $response = $this->simulateRequest('GET', '/users/123/posts/456');

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getJsonData();
        $this->assertEquals(123, $data['user_id']); // Should be converted to int
        $this->assertEquals(456, $data['post_id']);
        $this->assertEquals('integer', $data['user_id_type']);
        $this->assertIsArray($data['all_params']);
        $this->assertEquals('1', $data['query_page']); // No query string in simplified test
        $this->assertEquals('10', $data['query_limit']); // Default values
    }

    /**
     * Test HTTP integration with performance features
     */
    public function testHttpIntegrationWithPerformanceFeatures(): void
    {
        // Enable high performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        // Route that generates large JSON (should use pooling)
        $this->app->get(
            '/performance-http',
            function ($req, $res) {
                $largeData = $this->createLargeJsonPayload(50);

                return $res->status(200)
                      ->header('X-Performance-Mode', 'enabled')
                      ->header('X-Data-Size', (string) count($largeData))
                    ->json(
                        [
                            'performance_enabled' => true,
                            'hp_status' => HighPerformanceMode::getStatus(),
                            'large_dataset' => $largeData,
                            'memory_usage' => memory_get_usage(true) / 1024 / 1024
                        ]
                    );
            }
        );

        // Execute request
        $response = $this->simulateRequest('GET', '/performance-http');

        // Validate response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('enabled', $response->getHeader('X-Performance-Mode'));
        $this->assertEquals('50', $response->getHeader('X-Data-Size'));

        $data = $response->getJsonData();
        $this->assertTrue($data['performance_enabled']);
        $this->assertTrue($data['hp_status']['enabled']);
        $this->assertCount(50, $data['large_dataset']);
        $this->assertTrue(is_numeric($data['memory_usage'])); // Can be int or float

        // Verify HP mode is still active
        $finalStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($finalStatus['enabled']);
    }

    /**
     * Test file upload simulation
     */
    public function testFileUploadSimulation(): void
    {
        // File upload route
        $this->app->post(
            '/upload',
            function ($req, $res) {
                return $res->json(
                    [
                        'has_files' => !empty($_FILES),
                        'files_count' => count($_FILES),
                        'uploaded_files_psr7' => count($req->getUploadedFiles()),
                        'file_test_exists' => $req->hasFile('test_file'),
                        'file_info' => $req->file('test_file')
                    ]
                );
            }
        );

        // Simulate file upload (mock $_FILES)
        $_FILES = [
            'test_file' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/test_upload',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024
            ]
        ];

        $response = $this->simulateRequest(
            'POST',
            '/upload',
            [],
            [
                'Content-Type' => 'multipart/form-data'
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getJsonData();
        $this->assertTrue($data['has_files']);
        $this->assertEquals(1, $data['files_count']);
        $this->assertIsInt($data['uploaded_files_psr7']);

        // Clean up
        $_FILES = [];
    }

    /**
     * Test HTTP layer memory efficiency
     */
    public function testHttpLayerMemoryEfficiency(): void
    {
        $initialMemory = memory_get_usage(true);

        // Create multiple routes with different response types (unique paths)
        $uniqueId = substr(md5(__METHOD__), 0, 8);
        for ($i = 0; $i < 10; $i++) {
            $currentIndex = $i; // Create explicit copy to avoid closure issues
            $this->app->get(
                "/memory-test-{$uniqueId}-{$i}",
                function ($req, $res) use ($currentIndex) {
                    return $res->json(
                        [
                            'iteration' => $currentIndex,
                            'data' => array_fill(0, 10, "test_data_{$currentIndex}"),
                            'timestamp' => microtime(true),
                            'memory' => memory_get_usage(true)
                        ]
                    );
                }
            );
        }

        // Execute requests
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->simulateRequest('GET', "/memory-test-{$uniqueId}-{$i}");
        }

        // Validate all responses
        foreach ($responses as $i => $response) {
            $this->assertEquals(200, $response->getStatusCode());
            $data = $response->getJsonData();

            // Verify the response structure and handle missing keys
            $this->assertArrayHasKey('iteration', $data, "Response $i missing 'iteration' key");
            $this->assertArrayHasKey('data', $data, "Response $i missing 'data' key");

            $this->assertEquals($i, $data['iteration']);
            $this->assertCount(10, $data['data']);
        }

        // Force garbage collection
        gc_collect_cycles();

        // Check memory usage
        $finalMemory = memory_get_usage(true);
        $memoryGrowth = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        $this->assertLessThan(
            15,
            $memoryGrowth,
            "HTTP layer memory growth ({$memoryGrowth}MB) should be reasonable"
        );
    }
}
