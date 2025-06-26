<?php

namespace Express\Services;

use Express\Controller\Router;

/**
 * Service responsible for exporting OpenAPI documentation from Router routes.
 */
class OpenApiExporter
{
    /**
     * Generates OpenAPI documentation from multiple router routes.
     * @param array|string $routers Array of Router/RouterInstance instances or Router class name
     * @return array
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
        foreach ($allRoutes as $route) {
            $method = strtolower($route['method']);
            $meta = $route['metadata'] ?? [];
            $path = preg_replace('/:(\w+)/', '{$1}', $route['path']);
            $pathKey = is_string($path) ? $path : $route['path'];
            if (!isset($paths[$pathKey])) {
                $paths[$pathKey] = [];
            }
            $summary = $meta['summary'] ?? ($meta['description'] ?? ('Endpoint ' . $route['method'] . ' ' . $route['path']));
            $parameters = [];
            if (preg_match_all('/:([a-zA-Z0-9_]+)/', $route['path'], $matches)) {
                foreach ($matches[1] as $param) {
                    $type = 'string';
                    if (isset($meta['parameters'][$param]['type'])) {
                        $type = $meta['parameters'][$param]['type'];
                    }
                    $parameters[] = [
                        'name' => $param,
                        'in' => 'path',
                        'required' => true,
                        'schema' => [ 'type' => $type ]
                    ];
                }
            }
            if (isset($meta['parameters'])) {
                foreach ($meta['parameters'] as $name => $info) {
                    if (isset($info['in']) && $info['in'] !== 'path') {
                        $parameters[] = array_merge([
                            'name' => $name,
                            'in' => $info['in'],
                            'required' => $info['required'] ?? false,
                            'schema' => [ 'type' => $info['type'] ?? 'string' ]
                        ], isset($info['description']) ? ['description' => $info['description']] : []);
                    }
                }
            }
            $responses = [
                '200' => [ 'description' => 'Resposta de sucesso' ]
            ];
            if (isset($meta['responses']) && is_array($meta['responses'])) {
                foreach ($meta['responses'] as $code => $resp) {
                    $responses[$code] = is_array($resp) ? $resp : ['description' => $resp];
                }
            }
            // Add global error responses if they don't exist
            foreach ($globalErrors as $code => $err) {
                if (!isset($responses[$code])) {
                    $responses[$code] = $err;
                }
            }
            // Support for tags and descriptions
            $tags = [];
            if (isset($meta['tags'])) {
                if (is_array($meta['tags'])) {
                    $tags = $meta['tags'];
                } else {
                    $tags = [$meta['tags']];
                }
                foreach ($tags as $tag) {
                    $allTags[$tag] = true;
                }
            }
            // Tag descriptions (if provided)
            if (isset($meta['tagDescriptions']) && is_array($meta['tagDescriptions'])) {
                foreach ($meta['tagDescriptions'] as $tag => $desc) {
                    $tagDescriptions[$tag] = $desc;
                }
            }
            $routeDef = [
                'summary' => $summary,
                'parameters' => $parameters,
                'responses' => $responses
            ];
            if (!empty($tags)) {
                $routeDef['tags'] = $tags;
            }
            // Exemplos de request/response
            if (isset($meta['requestBody'])) {
                $routeDef['requestBody'] = $meta['requestBody'];
            }
            if (isset($meta['examples'])) {
                $routeDef['examples'] = $meta['examples'];
            }
            $paths[$pathKey][$method] = $routeDef;
        }
        // Monta o objeto OpenAPI
        $openapi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Express PHP API',
                'version' => '1.0.0',
            ],
            'servers' => $servers,
            'tags' => array_map(function($tag) use ($tagDescriptions) {
                return isset($tagDescriptions[$tag]) ? ['name' => $tag, 'description' => $tagDescriptions[$tag]] : ['name' => $tag];
            }, array_keys($allTags)),
            'paths' => $paths,
        ];
        return $openapi;
    }
}
