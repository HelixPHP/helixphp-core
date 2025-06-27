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
     * Compila pattern de rota para regex otimizada
     */
    public static function compilePattern(string $path): array
    {
        $cachedPattern = self::getPattern($path);
        $cachedParams = self::getParameters($path);

        if ($cachedPattern !== null && $cachedParams !== null) {
            return [
                'pattern' => $cachedPattern,
                'parameters' => $cachedParams
            ];
        }

        // Compilar pattern
        $pattern = $path;
        $parameters = [];

        // Encontra parâmetros na rota (:param)
        if (preg_match_all('/\/:([^\/]+)/', $pattern, $matches)) {
            $parameters = $matches[1];
        }

        // Converte parâmetros para regex otimizada
        $pattern = preg_replace('/\/:([^\/]+)/', '/([^/]+)', $pattern);

        // Permite barra final opcional
        $pattern = rtrim($pattern ?? '', '/');
        $compiledPattern = '#^' . $pattern . '/?$#';

        // Cache results
        self::setPattern($path, $compiledPattern);
        self::setParameters($path, $parameters);

        return [
            'pattern' => $compiledPattern,
            'parameters' => $parameters
        ];
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
