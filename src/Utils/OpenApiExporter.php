<?php

namespace Express\Utils;

use Express\Routing\Router;

/**
 * Service responsible for exporting OpenAPI documentation from Router routes.
 */
class OpenApiExporter
{
    /**
     * Generates OpenAPI documentation from multiple router routes.
     * @param array|string $routers Array of Router/RouterInstance instances or Router class name
     * @return array<string, mixed>
     */
    public static function export(mixed $routers, ?string $baseUrl = null): array
    {
        $allRoutes = [];
        if (is_string($routers)) {
            $allRoutes = $routers::getRoutes();
        } elseif (is_array($routers)) {
            foreach ($routers as $router) {
                if (is_string($router) && class_exists($router) && method_exists($router, 'getRoutes')) {
                    $allRoutes = array_merge($allRoutes, $router::getRoutes());
                } elseif (is_object($router) && method_exists($router, 'getRoutes')) {
                    $allRoutes = array_merge($allRoutes, $router->getRoutes());
                }
            }
        } elseif (is_object($routers) && method_exists($routers, 'getRoutes')) {
            $allRoutes = $routers->getRoutes();
        }

        $paths = [];
        $allTags = [];
        $tagDescriptions = [];

        // Default servers
        $servers = [];
        if ($baseUrl) {
            $servers[] = [ 'url' => $baseUrl, 'description' => 'Current environment' ];
        }
        $servers[] = [ 'url' => 'http://localhost:8080', 'description' => 'Local development' ];
        $servers[] = [ 'url' => 'https://api.example.com', 'description' => 'Production' ];
        $servers[] = [ 'url' => 'https://staging.api.example.com', 'description' => 'Staging' ];

        // Global error responses
        $globalErrors = [
            '400' => ['description' => 'Invalid request'],
            '401' => ['description' => 'Unauthorized'],
            '404' => ['description' => 'Not found'],
            '500' => ['description' => 'Internal server error']
        ];

        // Process routes
        foreach ($allRoutes as $route) {
            $path = self::convertPathParameters($route['url']);
            $method = strtolower($route['method']);

            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }

            // Extract documentation from comments
            $docInfo = self::parseDocumentation($route);
            $tags = $docInfo['tags'] ?? ['default'];
            $summary = $docInfo['summary'] ?? ucfirst($method) . ' ' . $path;
            $description = $docInfo['description'] ?? '';
            $parameters = $docInfo['parameters'] ?? [];
            $responses = $docInfo['responses'] ?? ['200' => ['description' => 'Successful response']];

            // Merge global errors
            $responses = array_merge($responses, $globalErrors);

            // Add tags to collection
            $allTags = array_merge($allTags, $tags);

            $paths[$path][$method] = [
                'tags' => $tags,
                'summary' => $summary,
                'description' => $description,
                'parameters' => $parameters,
                'responses' => $responses
            ];

            // Add request body for POST/PUT/PATCH
            if (in_array($method, ['post', 'put', 'patch'])) {
                $paths[$path][$method]['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['type' => 'object']
                        ]
                    ]
                ];
            }
        }

        // Create tag definitions
        $tagDefinitions = [];
        foreach (array_unique($allTags) as $tag) {
            $tagDefinitions[] = [
                'name' => $tag,
                'description' => $tagDescriptions[$tag] ?? ucfirst($tag) . ' operations'
            ];
        }

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Express-PHP API',
                'version' => '1.0.0',
                'description' => 'Auto-generated API documentation'
            ],
            'servers' => $servers,
            'tags' => $tagDefinitions,
            'paths' => $paths
        ];
    }

    /**
     * Convert Express route parameters to OpenAPI format
     */
    private static function convertPathParameters(string $path): string
    {
        return preg_replace('/:(\w+)/', '{$1}', $path);
    }

    /**
     * Parse documentation from route callback or comments
     */
    private static function parseDocumentation(array $route): array
    {
        $docInfo = [
            'tags' => ['default'],
            'summary' => '',
            'description' => '',
            'parameters' => [],
            'responses' => []
        ];

        // Try to extract documentation from callback if it's a closure
        if (isset($route['callback']) && is_callable($route['callback'])) {
            $reflection = new \ReflectionFunction($route['callback']);
            $docComment = $reflection->getDocComment();

            if ($docComment) {
                $docInfo = self::parseDocComment($docComment);
            }
        }

        return $docInfo;
    }

    /**
     * Parse PHPDoc comment
     */
    private static function parseDocComment(string $docComment): array
    {
        $docInfo = [
            'tags' => ['default'],
            'summary' => '',
            'description' => '',
            'parameters' => [],
            'responses' => []
        ];

        $lines = explode("\n", $docComment);
        $currentSection = 'description';
        $description = [];

        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B/*");

            if (empty($line)) continue;

            if (strpos($line, '@') === 0) {
                $parts = explode(' ', $line, 2);
                $tag = $parts[0];
                $content = $parts[1] ?? '';

                switch ($tag) {
                    case '@summary':
                        $docInfo['summary'] = $content;
                        break;
                    case '@tag':
                        $docInfo['tags'] = array_merge($docInfo['tags'], explode(',', $content));
                        break;
                    case '@param':
                        // Parse parameter documentation
                        if (preg_match('/(\w+)\s+\$(\w+)\s*(.*)/', $content, $matches)) {
                            $docInfo['parameters'][] = [
                                'name' => $matches[2],
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => self::phpTypeToOpenApi($matches[1])],
                                'description' => $matches[3] ?? ''
                            ];
                        }
                        break;
                    case '@return':
                    case '@response':
                        // Basic response documentation
                        $docInfo['responses']['200'] = ['description' => $content];
                        break;
                }
            } else {
                $description[] = $line;
            }
        }

        if (empty($docInfo['summary']) && !empty($description)) {
            $docInfo['summary'] = $description[0];
        }

        $docInfo['description'] = implode(' ', $description);
        $docInfo['tags'] = array_unique(array_filter($docInfo['tags']));

        return $docInfo;
    }

    /**
     * Convert PHP types to OpenAPI types
     */
    private static function phpTypeToOpenApi(string $phpType): string
    {
        $typeMap = [
            'int' => 'integer',
            'integer' => 'integer',
            'float' => 'number',
            'double' => 'number',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'string' => 'string',
            'array' => 'array',
            'object' => 'object'
        ];

        return $typeMap[strtolower($phpType)] ?? 'string';
    }
}
