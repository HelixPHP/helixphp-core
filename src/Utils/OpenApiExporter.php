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
     * Export OpenAPI specification
     */
    public function export(?string $baseUrl = null): array
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
    public static function exportStatic($app, ?string $baseUrl = null): array
    {
        $exporter = new self($app);
        return $exporter->export($baseUrl);
    }
}
