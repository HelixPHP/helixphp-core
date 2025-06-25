<?php

namespace Express\SRC\Services;

use Express\SRC\Controller\Router;

/**
 * Serviço responsável por exportar a documentação OpenAPI a partir das rotas do Router.
 */
class OpenApiExporter
{
    /**
     * Gera a documentação OpenAPI a partir das rotas de múltiplos routers.
     * @param array|string $routers Array de instâncias Router/RouterInstance ou nome da classe Router
     * @return array
     */
    public static function export($routers, $baseUrl = null)
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
        // Servers padrão
        $servers = [];
        if ($baseUrl) {
            $servers[] = [ 'url' => $baseUrl, 'description' => 'Ambiente atual' ];
        }
        $servers[] = [ 'url' => 'http://localhost:8080', 'description' => 'Desenvolvimento local' ];
        $servers[] = [ 'url' => 'https://api.exemplo.com', 'description' => 'Produção' ];
        $servers[] = [ 'url' => 'https://homolog.api.exemplo.com', 'description' => 'Homologação' ];
        // Respostas globais de erro
        $globalErrors = [
            '400' => ['description' => 'Requisição inválida'],
            '401' => ['description' => 'Não autorizado'],
            '404' => ['description' => 'Não encontrado'],
            '500' => ['description' => 'Erro interno do servidor']
        ];
        foreach ($allRoutes as $route) {
            $method = strtolower($route['method']);
            $meta = $route['metadata'] ?? [];
            $path = preg_replace('/:(\w+)/', '{$1}', $route['path']);
            if (!isset($paths[$path])) {
                $paths[$path] = [];
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
            // Adiciona respostas globais de erro se não existirem
            foreach ($globalErrors as $code => $err) {
                if (!isset($responses[$code])) {
                    $responses[$code] = $err;
                }
            }
            // Suporte a tags e descrições
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
            // Descrição de tags (se fornecida)
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
            $paths[$path][$method] = $routeDef;
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
