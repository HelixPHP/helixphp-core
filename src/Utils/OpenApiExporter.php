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
     *
     * @param  array|string $routers Array of Router/RouterInstance instances or Router class name
     * @return array<string, mixed>
     */
    public static function export($routers, ?string $baseUrl = null): array
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
            // O Router usa 'path', mas podemos aceitar tanto 'path' quanto 'url' para compatibilidade
            $path = self::convertPathParameters($route['path'] ?? $route['url'] ?? '');
            $method = strtolower($route['method']);

            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }

            // Extract documentation from comments
            $docInfo = self::parseDocumentation($route);
            $tags = $docInfo['tags'] ?? ['default'];
            $summary = $docInfo['summary'] ?? 'Endpoint ' . strtoupper($method) . ' ' . $path;
            $description = $docInfo['description'] ?? '';
            $parameters = $docInfo['parameters'] ?? [];

            // Determine if we should add global errors
            $hasCustomResponses = !empty($docInfo['responses']);
            $responses = $docInfo['responses'] ?? ['200' => ['description' => 'Successful response']];

            // Mesclar respostas globais sem sobrescrever as customizadas
            foreach ($globalErrors as $code => $error) {
                $codeStr = (string)$code;
                if (!isset($responses[$codeStr])) {
                    $responses[$codeStr] = $error;
                }
            }
            // Forçar todas as chaves para string (compatibilidade PHP 8.3/OpenAPI)
            $responses = array_combine(array_map('strval', array_keys($responses)), array_values($responses));

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
                'description' => ucfirst($tag) . ' operations'
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
    private static function convertPathParameters(?string $path): string
    {
        if (empty($path)) {
            return '/';
        }
        $result = preg_replace('/:(\w+)/', '{$1}', $path);
        return $result ?? $path;
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

        // Primeiro, vamos verificar se temos metadados explícitos na rota
        if (isset($route['metadata']) && is_array($route['metadata'])) {
            $metadata = $route['metadata'];

            if (isset($metadata['summary'])) {
                $docInfo['summary'] = $metadata['summary'];
            }

            if (isset($metadata['description'])) {
                $docInfo['description'] = $metadata['description'];
            }

            if (isset($metadata['tags'])) {
                $docInfo['tags'] = is_array($metadata['tags']) ? $metadata['tags'] : [$metadata['tags']];
            }

            if (isset($metadata['parameters'])) {
                $docInfo['parameters'] = self::convertParameters($metadata['parameters']);
            }

            if (isset($metadata['responses'])) {
                $docInfo['responses'] = self::normalizeResponses($metadata['responses']);
            }
        }

        // Se não temos summary e temos um handler, tentamos extrair da reflection
        if (empty($docInfo['summary'])) {
            $handler = $route['handler'] ?? $route['callback'] ?? null;
            if ($handler && is_callable($handler)) {
                try {
                    if (is_string($handler)) {
                        $reflection = new \ReflectionFunction($handler);
                    } elseif ($handler instanceof \Closure) {
                        $reflection = new \ReflectionFunction($handler);
                    } else {
                        $reflection = null;
                    }

                    if ($reflection) {
                        $docComment = $reflection->getDocComment();

                        if ($docComment) {
                            $parsedDoc = self::parseDocComment($docComment);
                            $docInfo = array_merge($docInfo, array_filter($parsedDoc));
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore reflection errors
                }
            }
        }

        // Se ainda não temos summary, gerar um padrão
        if (empty($docInfo['summary'])) {
            $method = strtoupper($route['method'] ?? 'GET');
            $path = $route['path'] ?? '/';
            $docInfo['summary'] = "Endpoint {$method} {$path}";
        }

        return $docInfo;
    }

    /**
     * Convert parameter definitions to OpenAPI format
     */
    private static function convertParameters(array $parameters): array
    {
        $converted = [];

        foreach ($parameters as $name => $config) {
            if (is_string($config)) {
                // Simple string definition
                $converted[] = [
                    'name' => $name,
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => self::phpTypeToOpenApi($config)]
                ];
            } elseif (is_array($config)) {
                // Full parameter definition
                $param = [
                    'name' => $name,
                    'in' => $config['in'] ?? 'path',
                    'required' => $config['required'] ?? true,
                    'schema' => [
                        'type' => self::phpTypeToOpenApi($config['type'] ?? 'string')
                    ]
                ];

                if (isset($config['description'])) {
                    $param['description'] = $config['description'];
                }

                $converted[] = $param;
            }
        }

        return $converted;
    }

    /**
     * Normalize response definitions to OpenAPI format
     */
    private static function normalizeResponses(array $responses): array
    {
        $normalized = [];

        foreach ($responses as $code => $response) {
            if (is_string($response)) {
                // Simple string description
                $normalized[$code] = ['description' => $response];
            } elseif (is_array($response) && isset($response['description'])) {
                // Already in proper format
                $normalized[$code] = $response;
            } else {
                // Fallback
                $normalized[$code] = ['description' => 'Response'];
            }
        }

        return $normalized;
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

            if (empty($line)) {
                continue;
            }

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
                            'description' => $matches[3]
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
     * Convert PHP type to OpenAPI type
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
