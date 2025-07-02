<?php

namespace Express\Tests\Services;

use PHPUnit\Framework\TestCase;
use Express\Utils\OpenApiExporter;
use Express\Routing\Router;

class OpenApiExporterTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset router state
        Router::clear();
    }

    public function testBasicExport(): void
    {
        // Create a route using the Router API
        Router::get(
            '/users',
            function () {
                return ['users' => []];
            },
            [
                'summary' => 'Get all users',
                'description' => 'Retrieve a list of all users'
            ]
        );

        $result = OpenApiExporter::export('Express\Routing\Router');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('openapi', $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('paths', $result);
        $this->assertEquals('3.0.0', $result['openapi']);
    }

    public function testExportWithBaseUrl(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(
            [
                [
                    'method' => 'GET',
                    'path' => '/test',
                    'handler' => function () {
                    },
                    'middlewares' => [],
                    'metadata' => []
                ]
            ]
        );

        $baseUrl = 'https://api.myapp.com';
        $result = OpenApiExporter::export('Express\Routing\Router', $baseUrl);

        $this->assertArrayHasKey('servers', $result);
        $this->assertGreaterThan(0, count($result['servers']));
        $this->assertEquals($baseUrl, $result['servers'][0]['url']);
    }

    public function testRouteWithParameters(): void
    {
        Router::get(
            '/users/:id',
            function ($id) {
                return ['user' => ['id' => $id]];
            },
            [
                'summary' => 'Get user by ID',
                'parameters' => [
                    'id' => [
                        'type' => 'integer',
                        'description' => 'User ID'
                    ]
                ]
            ]
        );

        $result = OpenApiExporter::export('Express\Routing\Router');

        $this->assertArrayHasKey('/users/{id}', $result['paths']);
        $this->assertArrayHasKey('get', $result['paths']['/users/{id}']);
        $this->assertArrayHasKey('parameters', $result['paths']['/users/{id}']['get']);

        $parameters = $result['paths']['/users/{id}']['get']['parameters'];
        $this->assertCount(1, $parameters);
        $this->assertEquals('id', $parameters[0]['name']);
        $this->assertEquals('path', $parameters[0]['in']);
        $this->assertTrue($parameters[0]['required']);
        $this->assertEquals('integer', $parameters[0]['schema']['type']);
    }

    public function testRouteWithQueryParameters(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(
            [
                [
                    'method' => 'GET',
                    'path' => '/users',
                    'handler' => function () {
                    },
                    'middlewares' => [],
                    'metadata' => [
                        'summary' => 'List users',
                        'parameters' => [
                            'page' => [
                                'in' => 'query',
                                'type' => 'integer',
                                'required' => false,
                                'description' => 'Page number'
                            ],
                            'limit' => [
                                'in' => 'query',
                                'type' => 'integer',
                                'required' => false,
                                'description' => 'Items per page'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $result = OpenApiExporter::export('Express\Routing\Router');

        $this->assertArrayHasKey('/users', $result['paths']);
        $this->assertArrayHasKey('get', $result['paths']['/users']);
        $this->assertArrayHasKey('parameters', $result['paths']['/users']['get']);

        $parameters = $result['paths']['/users']['get']['parameters'];
        $this->assertCount(2, $parameters);

        // Check query parameters
        $queryParams = array_filter(
            $parameters,
            function ($p) {
                return $p['in'] === 'query';
            }
        );
        $this->assertCount(2, $queryParams);
    }

    public function testRouteWithCustomResponses(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(
            [
                [
                    'method' => 'POST',
                    'path' => '/users',
                    'handler' => function () {
                    },
                    'middlewares' => [],
                    'metadata' => [
                        'summary' => 'Create user',
                        'responses' => [
                            '201' => [
                                'description' => 'User created successfully',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                                'name' => ['type' => 'string']
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '422' => 'Validation error'
                        ]
                    ]
                ]
            ]
        );

        $result = OpenApiExporter::export('Express\Routing\Router');

        $responses = $result['paths']['/users']['post']['responses'];
        $this->assertArrayHasKey('201', $responses);
        $this->assertArrayHasKey('422', $responses);
        $this->assertEquals('User created successfully', $responses['201']['description']);
        $this->assertEquals('Validation error', $responses['422']['description']);
    }

    public function testRouteWithTags(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(
            [
                [
                    'method' => 'GET',
                    'path' => '/users',
                    'handler' => function () {
                    },
                    'middlewares' => [],
                    'metadata' => [
                        'summary' => 'List users',
                        'tags' => ['Users', 'Management']
                    ]
                ]
            ]
        );

        $result = OpenApiExporter::export('Express\Routing\Router');

        $this->assertArrayHasKey('tags', $result['paths']['/users']['get']);
        $this->assertEquals(['Users', 'Management'], $result['paths']['/users']['get']['tags']);

        // Check if tags are added to global tags
        $this->assertArrayHasKey('tags', $result);
        $tagNames = array_column($result['tags'], 'name');
        $this->assertContains('Users', $tagNames);
        $this->assertContains('Management', $tagNames);
    }

    public function testMultipleHttpMethods(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(
            [
                [
                    'method' => 'GET',
                    'path' => '/users/:id',
                    'handler' => function () {
                    },
                    'middlewares' => [],
                    'metadata' => ['summary' => 'Get user']
                ],
                [
                    'method' => 'PUT',
                    'path' => '/users/:id',
                    'handler' => function () {
                    },
                    'middlewares' => [],
                    'metadata' => ['summary' => 'Update user']
                ],
                [
                    'method' => 'DELETE',
                    'path' => '/users/:id',
                    'handler' => function () {
                    },
                    'middlewares' => [],
                    'metadata' => ['summary' => 'Delete user']
                ]
            ]
        );

        $result = OpenApiExporter::export('Express\Routing\Router');

        $userPath = $result['paths']['/users/{id}'];
        $this->assertArrayHasKey('get', $userPath);
        $this->assertArrayHasKey('put', $userPath);
        $this->assertArrayHasKey('delete', $userPath);

        $this->assertEquals('Get user', $userPath['get']['summary']);
        $this->assertEquals('Update user', $userPath['put']['summary']);
        $this->assertEquals('Delete user', $userPath['delete']['summary']);
    }

    public function testEmptyRoutes(): void
    {
        $result = OpenApiExporter::export('Express\Routing\Router');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('openapi', $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('paths', $result);
        $this->assertEmpty($result['paths']);
    }

    public function testDefaultErrorResponses(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(
            [
                [
                    'method' => 'GET',
                    'path' => '/test',
                    'handler' => function () {
                    },
                    'middlewares' => [],
                    'metadata' => []
                ]
            ]
        );
        $result = OpenApiExporter::export('Express\Routing\Router');

        $responses = $result['paths']['/test']['get']['responses'];

        // Should include default error responses
        $this->assertArrayHasKey('400', $responses);
        $this->assertArrayHasKey('401', $responses);
        $this->assertArrayHasKey('404', $responses);
        $this->assertArrayHasKey('500', $responses);

        $this->assertEquals('Invalid request', $responses['400']['description']);
        $this->assertEquals('Unauthorized', $responses['401']['description']);
        $this->assertEquals('Not found', $responses['404']['description']);
        $this->assertEquals('Internal server error', $responses['500']['description']);
    }

    public function testComplexPath(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(
            [
                [
                    'method' => 'GET',
                    'path' => '/api/v1/users/:userId/posts/:postId',
                    'handler' => function () {
                    },
                    'middlewares' => [],
                    'metadata' => [
                        'summary' => 'Get user post',
                        'parameters' => [
                            'userId' => ['type' => 'integer'],
                            'postId' => ['type' => 'integer']
                        ]
                    ]
                ]
            ]
        );

        $result = OpenApiExporter::export('Express\Routing\Router');

        $this->assertArrayHasKey('/api/v1/users/{userId}/posts/{postId}', $result['paths']);

        $parameters = $result['paths']['/api/v1/users/{userId}/posts/{postId}']['get']['parameters'];
        $this->assertCount(2, $parameters);

        $paramNames = array_column($parameters, 'name');
        $this->assertContains('userId', $paramNames);
        $this->assertContains('postId', $paramNames);
    }

    public function testRouteWithoutMetadata(): void
    {
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(
            [
                [
                    'method' => 'GET',
                    'path' => '/test',
                    'handler' => function () {
                    },
                    'middlewares' => []
                // No metadata provided
                ]
            ]
        );

        $result = OpenApiExporter::export('Express\Routing\Router');

        $this->assertArrayHasKey('/test', $result['paths']);
        $this->assertArrayHasKey('get', $result['paths']['/test']);

        // Should have a default summary
        $this->assertArrayHasKey('summary', $result['paths']['/test']['get']);
        $this->assertStringContainsString('Endpoint GET /test', $result['paths']['/test']['get']['summary']);
    }
}
