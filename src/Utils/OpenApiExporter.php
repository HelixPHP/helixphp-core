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

            $spec['paths'][$path][$method] = [
                'summary' => 'Route: ' . $method . ' ' . $path,
                'responses' => [
                    '200' => [
                        'description' => 'Successful response'
                    ]
                ]
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
                'paths' => []
            ];
            
            // Process routes
            foreach ($routes as $route) {
                $path = $route['path'] ?? '/';
                $method = strtolower($route['method'] ?? 'get');
                
                // Convert Laravel-style parameters to OpenAPI format
                $path = preg_replace('/\:(\w+)/', '{$1}', $path);
                
                if (!isset($spec['paths'][$path])) {
                    $spec['paths'][$path] = [];
                }
                
                $spec['paths'][$path][$method] = [
                    'summary' => $route['summary'] ?? 'Route: ' . $method . ' ' . $path,
                    'responses' => [
                        '200' => [
                            'description' => 'Successful response'
                        ]
                    ]
                ];
                
                // Add parameters if they exist
                if (isset($route['parameters'])) {
                    $spec['paths'][$path][$method]['parameters'] = $route['parameters'];
                }
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
