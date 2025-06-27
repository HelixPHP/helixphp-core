<?php

namespace Express\Routing;

/**
 * Cache para rotas compiladas para melhorar performance
 */
class RouteCache
{
    /**
     * Cache de rotas compiladas
     * @var array<string, array>
     */
    private static array $compiledRoutes = [];

    /**
     * Cache de mapeamentos de parâmetros
     * @var array<string, array>
     */
    private static array $parameterMappings = [];

    /**
     * Cache de patterns compilados
     * @var array<string, string>
     */
    private static array $compiledPatterns = [];

    /**
     * Cache de patterns pré-compilados para evitar recompilação
     */
    private static array $fastParameterCache = [];

    /**
     * Cache de rotas por tipo (com ou sem parâmetros)
     */
    private static array $routeTypeCache = [
        'static' => [],
        'dynamic' => []
    ];

    /**
     * Estatísticas de cache
     * @var array<string, int>
     */
    private static array $stats = [
        'hits' => 0,
        'misses' => 0,
        'compilations' => 0
    ];

    /**
     * Obtém uma rota do cache
     */
    public static function get(string $key): ?array
    {
        if (isset(self::$compiledRoutes[$key])) {
            self::$stats['hits']++;
            return self::$compiledRoutes[$key];
        }

        self::$stats['misses']++;
        return null;
    }

    /**
     * Armazena uma rota no cache
     */
    public static function set(string $key, array $route): void
    {
        self::$compiledRoutes[$key] = $route;
    }

    /**
     * Gera chave de cache para método e caminho
     */
    public static function generateKey(string $method, string $path): string
    {
        return $method . '::' . $path;
    }

    /**
     * Obtém pattern compilado do cache
     */
    public static function getPattern(string $path): ?string
    {
        return self::$compiledPatterns[$path] ?? null;
    }

    /**
     * Armazena pattern compilado no cache
     */
    public static function setPattern(string $path, string $pattern): void
    {
        self::$compiledPatterns[$path] = $pattern;
        self::$stats['compilations']++;
    }

    /**
     * Obtém parâmetros do cache
     */
    public static function getParameters(string $path): ?array
    {
        return self::$parameterMappings[$path] ?? null;
    }

    /**
     * Armazena parâmetros no cache
     */
    public static function setParameters(string $path, array $parameters): void
    {
        self::$parameterMappings[$path] = $parameters;
    }

    /**
     * Compila pattern de rota para regex otimizada (versão melhorada)
     */
    public static function compilePattern(string $path): array
    {
        // Verifica cache rápido primeiro
        if (isset(self::$fastParameterCache[$path])) {
            return self::$fastParameterCache[$path];
        }

        $cachedPattern = self::getPattern($path);
        $cachedParams = self::getParameters($path);

        if ($cachedPattern !== null && $cachedParams !== null) {
            $result = [
                'pattern' => $cachedPattern,
                'parameters' => $cachedParams
            ];
            self::$fastParameterCache[$path] = $result;
            return $result;
        }

        // Verifica se é rota estática (sem parâmetros) - otimização especial
        if (strpos($path, ':') === false) {
            $result = [
                'pattern' => null, // Rotas estáticas não precisam de regex
                'parameters' => []
            ];
            self::$fastParameterCache[$path] = $result;
            self::$routeTypeCache['static'][$path] = true;
            return $result;
        }

        // Compilar pattern apenas para rotas dinâmicas
        $pattern = $path;
        $parameters = [];

        // Encontra parâmetros na rota (:param) - otimização melhorada
        if (preg_match_all('/\/:([^\/]+)/', $pattern, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $parameters[] = $match[1];
            }
        }

        // Converte parâmetros para regex otimizada - apenas uma vez
        if (!empty($parameters)) {
            $pattern = preg_replace('/\/:([^\/]+)/', '/([^/]+)', $pattern);
            $pattern = rtrim($pattern ?? '', '/');
            $compiledPattern = '#^' . $pattern . '/?$#';
        } else {
            $compiledPattern = null; // Não deveria acontecer, mas fallback
        }

        $result = [
            'pattern' => $compiledPattern,
            'parameters' => $parameters
        ];

        // Cache results em múltiplos lugares para acesso rápido
        self::setPattern($path, $compiledPattern);
        self::setParameters($path, $parameters);
        self::$fastParameterCache[$path] = $result;
        self::$routeTypeCache['dynamic'][$path] = true;

        return $result;
    }

    /**
     * Verifica se uma rota é estática (sem parâmetros)
     */
    public static function isStaticRoute(string $path): bool
    {
        if (isset(self::$routeTypeCache['static'][$path])) {
            return true;
        }
        if (isset(self::$routeTypeCache['dynamic'][$path])) {
            return false;
        }
        return strpos($path, ':') === false;
    }

    /**
     * Verifica se rota está em cache
     */
    public static function has(string $key): bool
    {
        return isset(self::$compiledRoutes[$key]);
    }

    /**
     * Remove uma rota do cache
     */
    public static function remove(string $key): void
    {
        unset(self::$compiledRoutes[$key]);
    }

    /**
     * Limpa todo o cache
     */
    public static function clear(): void
    {
        self::$compiledRoutes = [];
        self::$parameterMappings = [];
        self::$compiledPatterns = [];
        self::$fastParameterCache = [];
        self::$routeTypeCache = [
            'static' => [],
            'dynamic' => []
        ];
        self::$stats = [
            'hits' => 0,
            'misses' => 0,
            'compilations' => 0
        ];
    }

    /**
     * Obtém estatísticas do cache
     */
    public static function getStats(): array
    {
        $total = self::$stats['hits'] + self::$stats['misses'];
        $hitRate = $total > 0 ? (self::$stats['hits'] / $total) * 100 : 0;

        return [
            'hits' => self::$stats['hits'],
            'misses' => self::$stats['misses'],
            'total_requests' => $total,
            'hit_rate_percentage' => round($hitRate, 2),
            'compilations' => self::$stats['compilations'],
            'cached_routes' => count(self::$compiledRoutes),
            'cached_patterns' => count(self::$compiledPatterns),
            'memory_usage' => self::getMemoryUsage()
        ];
    }

    /**
     * Calcula uso de memória do cache
     */
    private static function getMemoryUsage(): string
    {
        $size = strlen(serialize(self::$compiledRoutes)) +
               strlen(serialize(self::$parameterMappings)) +
               strlen(serialize(self::$compiledPatterns));

        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return round($size / 1048576, 2) . ' MB';
        }
    }

    /**
     * Pré-aquece o cache com rotas conhecidas
     */
    public static function warmup(array $routes): void
    {
        foreach ($routes as $route) {
            $key = self::generateKey($route['method'], $route['path']);

            // Compila pattern antecipadamente
            $compiled = self::compilePattern($route['path']);

            $cachedRoute = array_merge($route, [
                'compiled_pattern' => $compiled['pattern'],
                'parameters' => $compiled['parameters']
            ]);

            self::set($key, $cachedRoute);
        }
    }

    /**
     * Obtém informações detalhadas do cache para debug
     */
    public static function getDebugInfo(): array
    {
        return [
            'cache_size' => [
                'routes' => count(self::$compiledRoutes),
                'patterns' => count(self::$compiledPatterns),
                'parameters' => count(self::$parameterMappings)
            ],
            'statistics' => self::getStats(),
            'sample_keys' => array_slice(array_keys(self::$compiledRoutes), 0, 10),
            'memory_details' => [
                'routes_memory' => strlen(serialize(self::$compiledRoutes)),
                'patterns_memory' => strlen(serialize(self::$compiledPatterns)),
                'parameters_memory' => strlen(serialize(self::$parameterMappings))
            ]
        ];
    }
}
