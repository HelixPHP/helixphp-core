<?php

declare(strict_types=1);

namespace PivotPHP\Core\Utils;

use PivotPHP\Core\Core\Application;

/**
 * OpenAPI Exporter
 *
 * Simple and effective OpenAPI specification generation for the microframework.
 * Provides basic OpenAPI functionality without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 *
 * @deprecated Use ApiDocumentationMiddleware instead for automatic documentation
 */
class OpenApiExporter
{
    /**
     * Application instance
     */
    private Application $app;

    /**
     * Constructor
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Generate OpenAPI specification
     */
    public function generate(?string $baseUrl = null): array
    {
        // Try to get routes from the router
        $router = $this->app->getRouter();
        $routes = method_exists($router, 'getRoutes') ? $router->getRoutes() : [];

        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'PivotPHP API',
                'version' => '1.2.0',
                'description' => 'Auto-generated API documentation'
            ],
            'servers' => [
                [
                    'url' => $baseUrl ?? 'http://localhost:8080',
                    'description' => 'Development server'
                ]
            ],
            'paths' => []
        ];

        // Simple route processing
        foreach ($routes as $route) {
            $path = $route['path'] ?? '/';
            $method = strtolower($route['method'] ?? 'get');

            if (!isset($spec['paths'][$path])) {
                $spec['paths'][$path] = [];
            }

            $responses = [
                '200' => [
                    'description' => 'Successful response'
                ]
            ];
            
            // Add default error responses if not a static route
            if (!isset($route['metadata']['static_route'])) {
                $responses = array_merge($responses, [
                    '400' => ['description' => 'Invalid request'],
                    '401' => ['description' => 'Unauthorized'],
                    '404' => ['description' => 'Not found'],
                    '500' => ['description' => 'Internal server error'],
                ]);
            }
            
            $spec['paths'][$path][$method] = [
                'summary' => $route['summary'] ?? ($route['metadata']['summary'] ?? 'Endpoint ' . strtoupper($method) . ' ' . $path),
                'responses' => $responses
            ];
        }

        return $spec;
    }

    /**
     * Static export method for backward compatibility
     */
    public static function exportStatic(Application $app, ?string $baseUrl = null): array
    {
        $exporter = new self($app);
        return $exporter->generate($baseUrl);
    }

    /**
     * Static export method (alias for backward compatibility)
     */
    public static function export(mixed $app, ?string $baseUrl = null): array
    {
        // If app is a string, try to get routes from Router class directly
        if (is_string($app)) {
            // Try to get routes from the Router class
            $routes = [];
            if (class_exists($app)) {
                // Try to get routes from static methods
                if (method_exists($app, 'getRoutes')) {
                    $routes = $app::getRoutes();
                }
            }
            
            $spec = [
                'openapi' => '3.0.0',
                'info' => [
                    'title' => 'PivotPHP API',
                    'version' => '1.2.0',
                    'description' => 'Auto-generated API documentation'
                ],
                'servers' => [
                    [
                        'url' => $baseUrl ?? 'http://localhost:8080',
                        'description' => 'Development server'
                    ]
                ],
                'paths' => [],
                'tags' => [] // Initialize tags array
            ];
            
            $globalTags = [];
            
            // Process routes
            foreach ($routes as $route) {
                $path = $route['path'] ?? '/';
                $method = strtolower($route['method'] ?? 'get');
                
                // Convert Laravel-style parameters to OpenAPI format
                $path = preg_replace('/\:(\w+)/', '{$1}', $path);
                
                if (!isset($spec['paths'][$path])) {
                    $spec['paths'][$path] = [];
                }
                
                $responses = [
                    '200' => [
                        'description' => 'Successful response'
                    ]
                ];
                
                // Add default error responses if not a static route
                if (!isset($route['metadata']['static_route'])) {
                    $defaultErrors = [
                        '400' => ['description' => 'Invalid request'],
                        '401' => ['description' => 'Unauthorized'],
                        '404' => ['description' => 'Not found'],
                        '500' => ['description' => 'Internal server error'],
                    ];
                    
                    // Only add default errors if they don't already exist
                    foreach ($defaultErrors as $code => $response) {
                        if (!isset($responses[$code])) {
                            $responses[$code] = $response;
                        }
                    }
                }
                
                $operationSpec = [
                    'summary' => $route['summary'] ?? ($route['metadata']['summary'] ?? 'Endpoint ' . strtoupper($method) . ' ' . $path),
                    'responses' => $responses
                ];
                
                // Add custom responses if they exist
                if (isset($route['metadata']['responses'])) {
                    $customResponses = $route['metadata']['responses'];
                    foreach ($customResponses as $code => $response) {
                        if (is_string($response)) {
                            $operationSpec['responses'][$code] = ['description' => $response];
                        } elseif (is_array($response)) {
                            $operationSpec['responses'][$code] = $response;
                        }
                    }
                }
                
                // Add tags if they exist
                if (isset($route['metadata']['tags'])) {
                    $operationSpec['tags'] = $route['metadata']['tags'];
                }
                
                $spec['paths'][$path][$method] = $operationSpec;
                
                // Collect global tags
                if (isset($route['metadata']['tags']) && is_array($route['metadata']['tags'])) {
                    foreach ($route['metadata']['tags'] as $tag) {
                        if (!in_array($tag, $globalTags)) {
                            $globalTags[] = $tag;
                        }
                    }
                }
                
                // Add parameters if they exist
                $parameterSource = $route['metadata']['parameters'] ?? null;
                if ($parameterSource) {
                    $parameters = [];
                    
                    if (is_array($parameterSource)) {
                        foreach ($parameterSource as $paramName => $paramConfig) {
                            if (is_string($paramName) && is_array($paramConfig)) {
                                $inValue = $paramConfig['in'] ?? 'path';
                                $parameters[] = [
                                    'name' => $paramName,
                                    'in' => $inValue,
                                    'required' => $paramConfig['required'] ?? ($inValue === 'path' ? true : false),
                                    'schema' => [
                                        'type' => $paramConfig['type'] ?? 'string'
                                    ],
                                    'description' => $paramConfig['description'] ?? ''
                                ];
                            }
                        }
                    }
                    
                    if (!empty($parameters)) {
                        $spec['paths'][$path][$method]['parameters'] = $parameters;
                    }
                }
            }
            
            // Add global tags to spec
            if (!empty($globalTags)) {
                $spec['tags'] = array_map(function($tag) {
                    return ['name' => $tag];
                }, $globalTags);
            }
            
            return $spec;
        }

        if ($app instanceof Application) {
            return self::exportStatic($app, $baseUrl);
        }
        
        // Fallback for non-Application objects
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'PivotPHP API',
                'version' => '1.2.0',
                'description' => 'Auto-generated API documentation'
            ],
            'servers' => [
                [
                    'url' => $baseUrl ?? 'http://localhost:8080',
                    'description' => 'Development server'
                ]
            ],
            'paths' => []
        ];
    }
}
