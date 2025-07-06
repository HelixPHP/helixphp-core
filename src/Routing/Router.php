<?php

namespace Helix\Routing;

use InvalidArgumentException;
use BadMethodCallException;
use Helix\Utils\Arr;

/**
 * Classe Router responsável pelo registro e identificação otimizada de rotas HTTP.
 * Inclui cache, indexação e otimizações integradas por padrão.
 */
class Router
{
    /**
     * Prefixo/base para rotas agrupadas.
     * @var string
     */
    private static string $current_group_prefix = '';

    /**
     * Lista de definições de rotas HTTP.
     * Cada rota é representada como um array associativo contendo informações como método, caminho e controlador.
     * Utilizado para compatibilidade com versões anteriores e para registro de novas rotas.
     * @var array<int, array<string, mixed>>
     */
    private static array $routes = [];

    /**
     * Rotas pré-compiladas para acesso rápido.
     * @var array<string, array>
     */
    private static array $preCompiledRoutes = [];

    /**
     * Índice de rotas por método para busca mais rápida.
     * @var array<string, array>
     */
    private static array $routesByMethod = [];

    /**
     * Cache de exact matches para rotas exatas.
     * @var array<string, array>
     */
    private static array $exactMatchCache = [];

    /**
     * Índice de rotas por grupo para acesso O(1).
     * @var array<string, array>
     */
    private static array $groupIndex = [];

    /**
     * Prefixos de grupos ativos ordenados por comprimento.
     * @var array<string>
     */
    private static array $sortedPrefixes = [];

    /**
     * Cache de matching de prefixos.
     * @var array<string, string>
     */
    private static array $prefixMatchCache = [];

    /**
     * Caminho padrão.
     * @var string
     */
    public const DEFAULT_PATH = '/';

    /**
     * Métodos HTTP aceitos.
     * @var array<string>
     */
    private static array $httpMethodsAccepted = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];

    /**
     * Middlewares de grupo por prefixo de rota.
     * @var array<string, callable[]>
     */
    private static array $groupMiddlewares = [];

    /**
     * Estatísticas de performance.
     * @var array<string, array>
     */
    private static array $stats = [];

    /**
     * Estatísticas de grupos de rotas.
     * @var array<string, array>
     */
    private static array $groupStats = [];

    /**
     * Route memory manager instance
     * @var RouteMemoryManager|null
     */
    private static ?RouteMemoryManager $memoryManager = null;

    /**
     * Permite adicionar métodos HTTP customizados.
     */
    public static function addHttpMethod(string $method): void
    {
        $method = strtoupper($method);
        if (!in_array($method, self::$httpMethodsAccepted)) {
            self::$httpMethodsAccepted[] = $method;
        }
    }

    /**
     * Define um prefixo/base para rotas agrupadas OU registra middlewares para um grupo.
     */
    public static function use(string $prev_path, callable ...$middlewares): void
    {
        if (empty($prev_path)) {
            $prev_path = '/';
        }
        self::$current_group_prefix = $prev_path;

        // Se middlewares foram passados, registra para o grupo
        if (!empty($middlewares)) {
            self::$groupMiddlewares[$prev_path] = $middlewares;
        }
    }

    /**
     * Registra um grupo de rotas com otimização integrada.
     */
    public static function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $startTime = microtime(true);

        // Normaliza o prefixo
        $prefix = self::normalizePrefix($prefix);

        // Armazena middlewares do grupo no cache
        if (!empty($middlewares)) {
            self::$groupMiddlewares[$prefix] = $middlewares;
        }

        // Define o prefixo atual
        $previousPrefix = self::$current_group_prefix;
        self::$current_group_prefix = $prefix;

        // Executa o callback para registrar as rotas do grupo
        call_user_func($callback);

        // Restaura o prefixo anterior
        self::$current_group_prefix = $previousPrefix;

        // Atualiza índices
        self::updateGroupIndex($prefix);
        self::updateSortedPrefixes();

        // Registra estatísticas
        $executionTime = (microtime(true) - $startTime) * 1000;
        $routesCount = count(self::$groupIndex[$prefix]);

        self::$stats['groups'][$prefix] = [
            'registration_time_ms' => $executionTime,
            'routes_count' => $routesCount,
            'has_middlewares' => !empty($middlewares),
            'last_updated' => microtime(true)
        ];

        // Inicializa estatísticas de grupo para métodos públicos
        self::$groupStats[$prefix] = [
            'routes_count' => $routesCount,
            'registration_time_ms' => $executionTime,
            'access_count' => 0,
            'total_access_time_ms' => 0,
            'has_middlewares' => !empty($middlewares),
            'cache_hits' => 0,
            'last_access' => null
        ];
    }

    /**
     * Adiciona uma nova rota com otimizações integradas.
     */
    public static function add(
        string $method,
        string $path,
        callable $handler,
        array $metadata = [],
        callable ...$middlewares
    ): void {
        if (empty($path)) {
            $path = self::DEFAULT_PATH;
        }
        if (!in_array(strtoupper($method), self::$httpMethodsAccepted)) {
            throw new InvalidArgumentException("Method {$method} is not supported");
        }
        $method = strtoupper($method);

        if (!is_callable($handler)) {
            throw new InvalidArgumentException('Handler must be a callable function');
        }

        foreach ($middlewares as $mw) {
            if (!is_callable($mw)) {
                throw new InvalidArgumentException('Middleware must be callable');
            }
        }

        // OTIMIZAÇÃO: processamento de path
        $path = self::optimizePathProcessing($path);

        $routeData = [
            'method' => $method,
            'path' => $path,
            'middlewares' => array_merge(self::getGroupMiddlewaresForPath($path), $middlewares),
            'handler' => $handler,
            'metadata' => self::sanitizeForJson($metadata)
        ];

        // Armazena na lista tradicional (compatibilidade)
        self::$routes[] = $routeData;

        // === OTIMIZAÇÕES INTEGRADAS ===

        $key = self::createRouteKey($method, $path);

        // Pre-compila pattern e parâmetros
        $compiled = RouteCache::compilePattern($path);

        $optimizedRoute = [
            'method' => $method,
            'path' => $path,
            'pattern' => $compiled['pattern'],
            'parameters' => $compiled['parameters'],
            'handler' => $handler,
            'metadata' => $metadata,
            'middlewares' => $routeData['middlewares'],
            'has_parameters' => !empty($compiled['parameters']),
            'group_prefix' => self::$current_group_prefix
        ];

        // Armazena na estrutura otimizada
        self::$preCompiledRoutes[$key] = $optimizedRoute;

        // Indexa por método para busca mais rápida
        if (!isset(self::$routesByMethod[$method])) {
            self::$routesByMethod[$method] = [];
        }
        self::$routesByMethod[$method][$key] = $optimizedRoute;

        // Cache no RouteCache
        RouteCache::set($key, $optimizedRoute);

        // Memory management integration
        $memoryManager = self::getMemoryManager();
        $memoryManager->trackRouteUsage($key);
        $memoryManager->checkMemoryUsage();
    }

    /**
     * Identifica rota de forma otimizada (método principal).
     */
    public static function identify(string $method, ?string $path = null): ?array
    {
        $method = strtoupper($method);

        if ($path === null) {
            $path = self::DEFAULT_PATH;
        }

        $startTime = microtime(true);

        // Memory management: track route access
        $routeKey = self::createRouteKey($method, $path);
        $memoryManager = self::getMemoryManager();
        $memoryManager->recordRouteAccess($routeKey);

        // 1. Tenta primeiro por grupos otimizados
        $route = self::identifyByGroup($method, $path);

        if ($route) {
            self::updateStats('identify_group_hit', $startTime);
            return $route;
        }

        // 2. Busca otimizada global
        $route = self::identifyOptimized($method, $path);

        if ($route) {
            self::updateStats('identify_optimized_hit', $startTime);
            return $route;
        }

        // 3. Fallback para busca tradicional (compatibilidade)
        $route = self::identifyTraditional($method, $path);

        if ($route) {
            self::updateStats('identify_traditional_hit', $startTime);
        } else {
            self::updateStats('identify_miss', $startTime);
        }

        return $route;
    }

    /**
     * Identificação otimizada por grupos.
     */
    public static function identifyByGroup(string $method, string $path): ?array
    {
        $startTime = microtime(true);

        // Verifica cache de matching de prefixos
        $cacheKey = $method . ':' . $path;
        if (isset(self::$prefixMatchCache[$cacheKey])) {
            $cachedPrefix = self::$prefixMatchCache[$cacheKey];
            if ($cachedPrefix && isset(self::$groupIndex[$cachedPrefix])) {
                $route = self::findRouteInGroup($cachedPrefix, $method, $path);

                // Atualiza estatísticas de acesso
                if ($route && isset(self::$groupStats[$cachedPrefix])) {
                    self::updateGroupStats($cachedPrefix, $startTime, true);
                }

                return $route;
            }
        }

        // Busca o grupo mais específico que coincide com o path
        $matchingPrefix = self::findMatchingPrefix($path);

        if ($matchingPrefix) {
            // Cache o resultado do matching
            self::$prefixMatchCache[$cacheKey] = $matchingPrefix;
            $route = self::findRouteInGroup($matchingPrefix, $method, $path);

            // Atualiza estatísticas de acesso
            if ($route && isset(self::$groupStats[$matchingPrefix])) {
                self::updateGroupStats($matchingPrefix, $startTime, false);
            }

            return $route;
        }

        return null;
    }

    /**
     * Identificação otimizada global (versão melhorada).
     */
    private static function identifyOptimized(string $method, string $path): ?array
    {
        $exactKey = self::createRouteKey($method, $path);

        // 1. Verifica cache de exact matches primeiro (O(1))
        if (isset(self::$exactMatchCache[$exactKey])) {
            return self::$exactMatchCache[$exactKey];
        }

        // 2. Verifica RouteCache (O(1))
        $cachedRoute = RouteCache::get($exactKey);
        if ($cachedRoute !== null) {
            self::$exactMatchCache[$exactKey] = $cachedRoute;
            return $cachedRoute;
        }

        // 3. Busca apenas nas rotas do método específico
        if (!isset(self::$routesByMethod[$method])) {
            return null;
        }

        // 4. OTIMIZAÇÃO: Separar rotas estáticas das dinâmicas
        $staticRoutes = [];
        $dynamicRoutes = [];

        foreach (self::$routesByMethod[$method] as $route) {
            if (!$route['has_parameters']) {
                $staticRoutes[] = $route;
            } else {
                $dynamicRoutes[] = $route;
            }
        }

        // 5. Primeiro busca em rotas estáticas (mais rápido)
        foreach ($staticRoutes as $route) {
            if ($route['path'] === $path) {
                self::$exactMatchCache[$exactKey] = $route;
                return $route;
            }
        }

        // 6. OTIMIZAÇÃO PARA PARÂMETROS: Pattern matching melhorado
        foreach ($dynamicRoutes as $route) {
            if (isset($route['pattern']) && $route['pattern'] !== null) {
                // Verifica se o pattern é válido antes de usar
                if (@preg_match($route['pattern'], $path, $matches)) {
                    // Cache o resultado para próximas consultas
                    $routeWithParams = $route;
                    if (!empty($route['parameters']) && count($matches) > 1) {
                        $params = [];
                        for ($i = 1; $i < count($matches); $i++) {
                            if (isset($route['parameters'][$i - 1])) {
                                $params[$route['parameters'][$i - 1]] = $matches[$i];
                            }
                        }
                        $routeWithParams['matched_params'] = $params;
                    }

                    // Cache para próximas consultas idênticas
                    self::$exactMatchCache[$exactKey] = $routeWithParams;
                    return $routeWithParams;
                }
            }
        }

        return null;
    }

    /**
     * Identificação tradicional (fallback para compatibilidade).
     */
    private static function identifyTraditional(string $method, string $path): ?array
    {
        // Filter routes based on method
        $routes = array_filter(
            self::$routes,
            function ($route) use ($method) {
                return $route['method'] === $method;
            }
        );

        if (empty($routes)) {
            return null;
        }

        // 1. Tenta encontrar rota estática (exata)
        foreach ($routes as $route) {
            if ($route['path'] === $path) {
                return $route;
            }
        }

        // 2. Tenta encontrar rota dinâmica (com parâmetros)
        foreach ($routes as $route) {
            $routePath = is_string($route['path']) ? $route['path'] : '';
            $pattern = preg_replace('/\/(:[^\/]+)/', '/([^/]+)', $routePath);
            if ($pattern === null) {
                $pattern = $routePath;
            }
            $pattern = rtrim($pattern, '/');
            $pattern = '#^' . $pattern . '/?$#';
            if ($routePath === self::DEFAULT_PATH) {
                if ($path === self::DEFAULT_PATH) {
                    return $route;
                }
            } elseif (preg_match($pattern, $path)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Métodos auxiliares para otimizações
     */
    private static function createRouteKey(string $method, string $path): string
    {
        return $method . '::' . $path;
    }

    private static function normalizePrefix(string $prefix): string
    {
        if (empty($prefix) || $prefix === '/') {
            return '/';
        }

        $prefix = '/' . trim($prefix, '/');
        $normalized = preg_replace('/\/+/', '/', $prefix);
        return $normalized !== null ? $normalized : $prefix;
    }

    private static function findMatchingPrefix(string $path): ?string
    {
        foreach (self::$sortedPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return $prefix;
            }
        }
        return null;
    }

    private static function findRouteInGroup(string $prefix, string $method, string $path): ?array
    {
        if (!isset(self::$groupIndex[$prefix])) {
            return null;
        }

        $groupRoutes = self::$groupIndex[$prefix];

        if (!isset($groupRoutes[$method])) {
            return null;
        }

        foreach ($groupRoutes[$method] as $route) {
            // Exact match primeiro
            if ($route['path'] === $path) {
                return self::enrichRouteWithGroupMiddlewares($route, $prefix);
            }
        }

        // Pattern matching para rotas com parâmetros
        foreach ($groupRoutes[$method] as $route) {
            if (isset($route['pattern']) && preg_match($route['pattern'], $path)) {
                return self::enrichRouteWithGroupMiddlewares($route, $prefix);
            }
        }

        return null;
    }

    private static function enrichRouteWithGroupMiddlewares(array $route, string $prefix): array
    {
        if (isset(self::$groupMiddlewares[$prefix])) {
            $groupMiddlewares = self::$groupMiddlewares[$prefix];
            $route['middlewares'] = array_merge($groupMiddlewares, $route['middlewares'] ?? []);
        }
        return $route;
    }

    private static function updateGroupIndex(string $prefix): void
    {
        $routes = self::getRoutesByPrefix($prefix);

        if (!isset(self::$groupIndex[$prefix])) {
            self::$groupIndex[$prefix] = [];
        }

        foreach ($routes as $route) {
            $method = $route['method'];
            if (!isset(self::$groupIndex[$prefix][$method])) {
                self::$groupIndex[$prefix][$method] = [];
            }
            self::$groupIndex[$prefix][$method][] = $route;
        }
    }

    private static function updateSortedPrefixes(): void
    {
        self::$sortedPrefixes = array_keys(self::$groupIndex);

        usort(
            self::$sortedPrefixes,
            function ($a, $b) {
                return strlen($b) - strlen($a);
            }
        );
    }

    private static function getRoutesByPrefix(string $prefix): array
    {
        $routes = [];

        foreach (self::$preCompiledRoutes as $key => $route) {
            if (strpos($route['path'], $prefix) === 0) {
                $routes[] = $route;
            }
        }

        return $routes;
    }

    private static function updateStats(string $key, float $startTime): void
    {
        $time = (microtime(true) - $startTime) * 1000;

        if (!isset(self::$stats[$key])) {
            self::$stats[$key] = ['count' => 0, 'total_time' => 0, 'avg_time' => 0];
        }

        self::$stats[$key]['count']++;
        self::$stats[$key]['total_time'] += $time;
        self::$stats[$key]['avg_time'] = self::$stats[$key]['total_time'] / self::$stats[$key]['count'];
    }

    /**
     * Pré-aquece caches (método público para uso após registrar rotas).
     */
    public static function warmupCache(): void
    {
        RouteCache::warmup(self::$routes);

        // Pré-compila todas as rotas não compiladas
        foreach (self::$routes as $route) {
            $method = is_string($route['method']) ? $route['method'] : 'GET';
            $path = is_string($route['path']) ? $route['path'] : '/';

            $key = self::createRouteKey($method, $path);
            if (!isset(self::$preCompiledRoutes[$key])) {
                $compiled = RouteCache::compilePattern($path);

                $optimizedRoute = [
                    'method' => $route['method'],
                    'path' => $route['path'],
                    'pattern' => $compiled['pattern'],
                    'parameters' => $compiled['parameters'],
                    'handler' => $route['handler'],
                    'metadata' => $route['metadata'] ?? [],
                    'middlewares' => $route['middlewares'] ?? [],
                    'has_parameters' => !empty($compiled['parameters'])
                ];

                self::$preCompiledRoutes[$key] = $optimizedRoute;

                if (!isset(self::$routesByMethod[$route['method']])) {
                    self::$routesByMethod[$route['method']] = [];
                }
                self::$routesByMethod[$route['method']][$key] = $optimizedRoute;
            }
        }

        // Aquece grupos
        self::warmupGroups();
    }

    public static function warmupGroups(array $prefixes = []): void
    {
        if (empty($prefixes)) {
            $prefixes = array_keys(self::$groupIndex);
        }

        foreach ($prefixes as $prefix) {
            self::findMatchingPrefix($prefix);
        }
    }

    /**
     * Obtém estatísticas de performance.
     */
    public static function getStats(): array
    {
        $routeStats = self::$stats;
        $routeStats['cache_stats'] = RouteCache::getStats();
        $routeStats['total_routes'] = count(self::$routes);
        $routeStats['compiled_routes'] = count(self::$preCompiledRoutes);
        $routeStats['groups'] = self::$stats['groups'] ?? [];

        return $routeStats;
    }

    /**
     * Limpa todos os caches e estatísticas.
     */
    public static function clearCache(): void
    {
        self::$preCompiledRoutes = [];
        self::$routesByMethod = [];
        self::$exactMatchCache = [];
        self::$groupIndex = [];
        self::$sortedPrefixes = [];
        self::$prefixMatchCache = [];
        self::$stats = [];
        RouteCache::clear();
    }

    /**
     * Métodos de compatibilidade (mantidos para não quebrar código existente)
     */

    public static function __callStatic(string $method, array $args): mixed
    {
        if (in_array(strtoupper($method), self::$httpMethodsAccepted)) {
            $path = array_shift($args);
            self::add(strtoupper($method), $path, ...$args);
            return null;
        }

        if (method_exists(self::class, $method)) {
            return self::{$method}(...$args);
        }
        throw new BadMethodCallException("Method {$method} does not exist in " . self::class);
    }

    public static function toString(): string
    {
        $output = '';
        foreach (self::$routes as $route) {
            $method = is_string($route['method']) ? $route['method'] : 'UNKNOWN';
            $path = is_string($route['path']) ? $route['path'] : '/';
            $handlerType = is_callable($route['handler']) ? 'Callable' : 'Not Callable';

            $output .= sprintf(
                "%s %s => %s\n",
                $method,
                $path,
                $handlerType
            );
        }
        return $output;
    }

    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Registra uma rota GET.
     */
    public static function get(string $path, callable $handler, array $metadata = [], callable ...$middlewares): void
    {
        self::add('GET', $path, $handler, $metadata, ...$middlewares);
    }

    /**
     * Registra uma rota POST.
     */
    public static function post(string $path, callable $handler, array $metadata = [], callable ...$middlewares): void
    {
        self::add('POST', $path, $handler, $metadata, ...$middlewares);
    }

    /**
     * Registra uma rota PUT.
     */
    public static function put(string $path, callable $handler, array $metadata = [], callable ...$middlewares): void
    {
        self::add('PUT', $path, $handler, $metadata, ...$middlewares);
    }

    /**
     * Registra uma rota DELETE.
     */
    public static function delete(string $path, callable $handler, array $metadata = [], callable ...$middlewares): void
    {
        self::add('DELETE', $path, $handler, $metadata, ...$middlewares);
    }

    /**
     * Registra uma rota PATCH.
     */
    public static function patch(string $path, callable $handler, array $metadata = [], callable ...$middlewares): void
    {
        self::add('PATCH', $path, $handler, $metadata, ...$middlewares);
    }

    /**
     * Registra uma rota OPTIONS.
     */
    public static function options(
        string $path,
        callable $handler,
        array $metadata = [],
        callable ...$middlewares
    ): void {
        self::add('OPTIONS', $path, $handler, $metadata, ...$middlewares);
    }

    /**
     * Registra uma rota HEAD.
     */
    public static function head(string $path, callable $handler, array $metadata = [], callable ...$middlewares): void
    {
        self::add('HEAD', $path, $handler, $metadata, ...$middlewares);
    }

    /**
     * Registra uma rota para todos os métodos HTTP.
     */
    public static function any(string $path, callable $handler, array $metadata = [], callable ...$middlewares): void
    {
        foreach (self::$httpMethodsAccepted as $method) {
            self::add($method, $path, $handler, $metadata, ...$middlewares);
        }
    }

    public static function getHttpMethodsAccepted(): array
    {
        return self::$httpMethodsAccepted;
    }

    /**
     * Remove closures, objetos e recursos de arrays recursivamente
     */
    private static function sanitizeForJson(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    $out[$k] = self::sanitizeForJson($v);
                } elseif (is_scalar($v) || is_null($v)) {
                    $out[$k] = $v;
                } elseif (is_object($v)) {
                    if ($v instanceof \stdClass) {
                        $out[$k] = self::sanitizeForJson((array)$v);
                    } else {
                        $out[$k] = '[object]';
                    }
                } elseif (is_resource($v)) {
                    $out[$k] = '[resource]';
                } else {
                    $out[$k] = '[unserializable]';
                }
            }
            return $out;
        }
        if (is_scalar($value) || is_null($value)) {
            return $value;
        }
        if (is_object($value)) {
            if ($value instanceof \stdClass) {
                return self::sanitizeForJson((array)$value);
            }
            return '[object]';
        }
        if (is_resource($value)) {
            return '[resource]';
        }
        return '[unserializable]';
    }

    /**
     * Obtém estatísticas dos grupos registrados
     */
    public static function getGroupStats(): array
    {
        $stats = [];

        foreach (self::$groupStats as $prefix => $data) {
            $stats[$prefix] = [
                'routes_count' => $data['routes_count'],
                'registration_time_ms' => round($data['registration_time_ms'], 3),
                'access_count' => $data['access_count'],
                'avg_access_time_ms' => $data['access_count'] > 0
                    ? round($data['total_access_time_ms'] / $data['access_count'], 6)
                    : 0,
                'has_middlewares' => $data['has_middlewares'],
                'cache_hit_ratio' => $data['access_count'] > 0
                    ? ($data['cache_hits'] / $data['access_count'])
                    : 0
            ];
        }

        return $stats;
    }

    /**
     * Atualiza estatísticas de acesso de um grupo
     */
    private static function updateGroupStats(string $prefix, float $startTime, bool $cacheHit): void
    {
        if (!isset(self::$groupStats[$prefix])) {
            return;
        }

        $accessTime = (microtime(true) - $startTime) * 1000;

        self::$groupStats[$prefix]['access_count']++;
        self::$groupStats[$prefix]['total_access_time_ms'] += $accessTime;
        self::$groupStats[$prefix]['last_access'] = microtime(true);

        if ($cacheHit) {
            self::$groupStats[$prefix]['cache_hits']++;
        }
    }

    /**
     * Benchmark de acesso a rotas de um grupo específico
     */
    public static function benchmarkGroupAccess(string $prefix, int $iterations = 1000): array
    {
        // Verifica se o grupo existe
        if (!isset(self::$groupStats[$prefix])) {
            throw new \InvalidArgumentException("Group prefix '{$prefix}' not found");
        }

        // Obtém rotas do grupo
        $groupRoutes = self::getRoutesByPrefix($prefix);
        if (empty($groupRoutes)) {
            throw new \InvalidArgumentException("No routes found for group '{$prefix}'");
        }

        // Seleciona uma rota de teste
        $testRoute = $groupRoutes[0];
        $method = $testRoute['method'];

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            self::identifyByGroup($method, $testRoute['path']);
        }

        $end = microtime(true);
        $totalTime = ($end - $start) * 1000;

        return [
            'group_prefix' => $prefix,
            'test_route' => $testRoute['path'],
            'method' => $method,
            'iterations' => $iterations,
            'total_time_ms' => round($totalTime, 3),
            'avg_time_microseconds' => round(($totalTime / $iterations) * 1000, 3),
            'ops_per_second' => round($iterations / ($end - $start), 0),
            'group_stats' => self::$groupStats[$prefix]
        ];
    }

    /**
     * Limpa todas as rotas, caches e estatísticas
     */
    public static function clear(): void
    {
        self::$routes = [];
        self::$routesByMethod = [];
        self::$exactMatchCache = [];
        self::$groupIndex = [];
        self::$prefixMatchCache = [];
        self::$sortedPrefixes = [];
        self::$stats = [];
        self::$groupStats = [];
        self::$groupMiddlewares = [];
        self::$current_group_prefix = '';

        // Limpa cache do RouteCache também
        RouteCache::clear();
    }

    /**
     * Otimiza processamento de path
     */
    private static function optimizePathProcessing(string $path): string
    {
        // Aplica prefixo de grupo se houver
        if (!empty(self::$current_group_prefix) && self::$current_group_prefix !== '/') {
            if (strpos($path, self::$current_group_prefix) !== 0) {
                $path = self::$current_group_prefix . $path;
                // OTIMIZAÇÃO: regex apenas quando necessário
                if (strpos($path, '//') !== false) {
                    $normalizedPath = preg_replace('/\/+/', '/', $path);
                    $path = $normalizedPath !== null ? $normalizedPath : $path;
                }
            }
        }

        // Ensure the path starts with a slash
        if (!empty($path) && $path[0] !== '/') {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * Obtém middlewares de grupo para path (lazy loading)
     */
    private static function getGroupMiddlewaresForPath(string $path): array
    {
        if (empty(self::$groupMiddlewares)) {
            return [];
        }

        $groupMiddlewares = [];
        foreach (self::$groupMiddlewares as $prefix => $groupMws) {
            if (!empty($path) && strpos($path, $prefix) === 0) {
                $groupMiddlewares = array_merge($groupMiddlewares, $groupMws);
            }
        }

        return $groupMiddlewares;
    }

    /**
     * Get or create route memory manager instance
     */
    private static function getMemoryManager(): RouteMemoryManager
    {
        if (self::$memoryManager === null) {
            self::$memoryManager = new RouteMemoryManager();
        }
        return self::$memoryManager;
    }
}
