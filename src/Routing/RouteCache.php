<?php

namespace PivotPHP\Core\Routing;

use PivotPHP\Core\Utils\SerializationCache;

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
     * Cache para cálculos de uso de memória
     * @var array<string, mixed>|null
     */
    private static ?array $memoryUsageCache = null;

    /**
     * Hash dos dados para invalidação do cache de memória
     */
    private static ?string $lastDataHash = null;

    /**
     * Mapeamento de shortcuts para constraints regex
     */
    private const CONSTRAINT_SHORTCUTS = [
        'int' => '\d+',
        'slug' => '[a-z0-9-]+',
        'alpha' => '[a-zA-Z]+',
        'alnum' => '[a-zA-Z0-9]+',
        'uuid' => '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}',
        'date' => '\d{4}-\d{2}-\d{2}',
        'year' => '\d{4}',
        'month' => '\d{2}',
        'day' => '\d{2}'
    ];

    /**
     * Padrões regex perigosos para detecção de ReDoS
     */
    private const DANGEROUS_PATTERNS = [
        '(\w+)*\w*',
        '(.+)+',
        '(a*)*',
        '(a|a)*',
        '(a+)+b'
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
        self::invalidateMemoryCache();
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
        self::invalidateMemoryCache();
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
        self::invalidateMemoryCache();
    }

    /**
     * Compila pattern de rota para regex otimizada (versão melhorada com suporte a constraints)
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
        if (strpos($path, ':') === false && strpos($path, '{') === false) {
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
        $position = 0;

        // Primeiro, processa regex completo entre chaves {}
        $pattern = preg_replace_callback(
            '/\{([^\}]+)\}/',
            function ($matches) use (&$position) {
                $position++;
                return $matches[1]; // Retorna o regex sem as chaves
            },
            $pattern
        );

        // Garante que pattern não é null após primeira transformação
        if ($pattern === null) {
            $pattern = '';
        }

        // Depois, processa parâmetros com constraints (:param<constraint>)
        $pattern = preg_replace_callback(
            '/:([a-zA-Z_][a-zA-Z0-9_]*)(?:<([^>]+)>)?/',
            function ($matches) use (&$parameters, &$position) {
                $paramName = $matches[1];
                $constraint = $matches[2] ?? '[^/]+'; // Constraint padrão

                // Resolve shortcuts de constraints
                $constraint = self::resolveConstraintShortcut($constraint);

                // Valida segurança do regex
                if (!self::isRegexSafe($constraint)) {
                    throw new \InvalidArgumentException(
                        "Unsafe regex pattern detected in route parameter '{$paramName}': {$constraint}"
                    );
                }

                $parameters[] = [
                    'name' => $paramName,
                    'position' => $position++,
                    'constraint' => $constraint
                ];

                return '(' . $constraint . ')';
            },
            $pattern
        );

        // Garante que pattern não é null
        if ($pattern === null) {
            $pattern = '';
        }

        // Remove barras duplicadas e prepara o pattern final
        $pattern = preg_replace('#/+#', '/', $pattern);
        $pattern = rtrim($pattern ?? '', '/');
        $compiledPattern = '#^' . $pattern . '/?$#';

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
     * Resolve shortcuts de constraints para regex completo
     */
    private static function resolveConstraintShortcut(string $constraint): string
    {
        return self::CONSTRAINT_SHORTCUTS[$constraint] ?? $constraint;
    }

    /**
     * Verifica se um pattern regex é seguro contra ReDoS
     */
    private static function isRegexSafe(string $pattern): bool
    {
        // Verifica comprimento máximo
        if (strlen($pattern) > 200) {
            return false;
        }

        // Verifica padrões perigosos conhecidos
        foreach (self::DANGEROUS_PATTERNS as $dangerous) {
            if (strpos($pattern, $dangerous) !== false) {
                return false;
            }
        }

        // Verifica nested quantifiers perigosos
        // Procura por quantifiers repetidos como (x+)+ ou (x*)*
        if (preg_match('/\([^)]*[\*\+]\)[*+]/', $pattern)) {
            return false;
        }

        // Verifica backtracking excessivo
        if (preg_match('/\([^)]*\|[^)]*\)[\*\+]/', $pattern) && substr_count($pattern, '|') > 5) {
            return false;
        }

        // Verifica alternations excessivas
        if (substr_count($pattern, '|') > 10) {
            return false;
        }

        // Tenta compilar o regex para verificar se é válido
        try {
            @preg_match('#' . $pattern . '#', '');
            return preg_last_error() === PREG_NO_ERROR;
        } catch (\Exception $e) {
            return false;
        }
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
        return strpos($path, ':') === false && strpos($path, '{') === false;
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
        self::invalidateMemoryCache();
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
     * Invalida o cache de uso de memória
     */
    private static function invalidateMemoryCache(): void
    {
        self::$memoryUsageCache = null;
        self::$lastDataHash = null;
    }

    /**
     * Limpa todos os caches incluindo cache de serialização
     */
    public static function clearCache(): void
    {
        self::$compiledRoutes = [];
        self::$parameterMappings = [];
        self::$compiledPatterns = [];
        self::$fastParameterCache = [];
        self::$routeTypeCache = ['static' => [], 'dynamic' => []];
        self::$stats = ['hits' => 0, 'misses' => 0, 'compilations' => 0];
        self::invalidateMemoryCache();

        // Limpa cache de serialização relacionado
        SerializationCache::clearCache();
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
     * Calcula uso de memória do cache com cache otimizado para melhor performance
     */
    private static function getMemoryUsage(): string
    {
        // Gera hash dos dados atuais de forma mais eficiente
        $currentDataHash = md5(
            count(self::$compiledRoutes) . '|' .
            count(self::$compiledPatterns) . '|' .
            count(self::$parameterMappings) . '|' .
            serialize(array_keys(self::$compiledRoutes))
        );

        // Se o cache é válido, retorna os dados em cache
        if (self::$memoryUsageCache !== null && self::$lastDataHash === $currentDataHash) {
            $formatted = self::$memoryUsageCache['formatted'] ?? '';
            return is_string($formatted) ? $formatted : '';
        }

        // Recalcula o uso de memória usando cache de serialização otimizado
        $objects = [
            'routes' => self::$compiledRoutes,
            'patterns' => self::$compiledPatterns,
            'parameters' => self::$parameterMappings
        ];

        $cacheKeys = ['route_cache_routes', 'route_cache_patterns', 'route_cache_parameters'];
        $size = SerializationCache::getTotalSerializedSize(array_values($objects), $cacheKeys);

        $formatted = '';
        if ($size < 1024) {
            $formatted = $size . ' B';
        } elseif ($size < 1048576) {
            $formatted = round($size / 1024, 2) . ' KB';
        } else {
            $formatted = round($size / 1048576, 2) . ' MB';
        }

        // Calcula tamanhos individuais usando cache
        $routesSize = SerializationCache::getSerializedSize(self::$compiledRoutes, 'route_cache_routes');
        $patternsSize = SerializationCache::getSerializedSize(self::$compiledPatterns, 'route_cache_patterns');
        $parametersSize = SerializationCache::getSerializedSize(self::$parameterMappings, 'route_cache_parameters');

        // Armazena no cache
        self::$memoryUsageCache = [
            'raw_size' => $size,
            'formatted' => $formatted,
            'routes_memory' => $routesSize,
            'patterns_memory' => $patternsSize,
            'parameters_memory' => $parametersSize,
            'serialization_stats' => SerializationCache::getStats()
        ];
        self::$lastDataHash = $currentDataHash;

        return $formatted;
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

            $cachedRoute = array_merge(
                $route,
                [
                    'compiled_pattern' => $compiled['pattern'],
                    'parameters' => $compiled['parameters']
                ]
            );

            self::set($key, $cachedRoute);
        }
    }

    /**
     * Obtém informações detalhadas do cache para debug
     */
    public static function getDebugInfo(): array
    {
        // Garante que o cache de memória está atualizado
        self::getMemoryUsage();

        return [
            'cache_size' => [
                'routes' => count(self::$compiledRoutes),
                'patterns' => count(self::$compiledPatterns),
                'parameters' => count(self::$parameterMappings)
            ],
            'statistics' => self::getStats(),
            'sample_keys' => array_slice(array_keys(self::$compiledRoutes), 0, 10),
            'memory_details' => [
                'routes_memory' => self::$memoryUsageCache['routes_memory'] ?? 0,
                'patterns_memory' => self::$memoryUsageCache['patterns_memory'] ?? 0,
                'parameters_memory' => self::$memoryUsageCache['parameters_memory'] ?? 0
            ],
            'constraint_shortcuts' => self::CONSTRAINT_SHORTCUTS
        ];
    }

    /**
     * Obtém lista de shortcuts disponíveis para constraints
     */
    public static function getAvailableShortcuts(): array
    {
        return self::CONSTRAINT_SHORTCUTS;
    }
}
