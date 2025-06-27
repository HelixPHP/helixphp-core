<?php

namespace Express\Routing;

use InvalidArgumentException;
use BadMethodCallException;

/**
 * Classe Router responsável pelo registro e identificação de rotas HTTP.
 * Permite agrupar rotas, registrar handlers e identificar rotas para requisições.
 */
class Router
{
    /**
     * Prefixo/base para rotas agrupadas.
     *
     * @var string
     */
    private static string $current_group_prefix = '';

    /**
     * Lista de rotas registradas.
     *
     * @var array<int, array<string, mixed>>
     */
    private static array $routes = [];

    /**
     * Caminho padrão.
     *
     * @var string
     */
    public const DEFAULT_PATH = '/';

    /**
     * Métodos HTTP aceitos.
     *
     * @var array<string>
     */
    private static array $httpMethodsAccepted = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];

    /**
     * Middlewares de grupo por prefixo de rota.
     *
     * @var array<string, callable[]>
     */
    private static array $groupMiddlewares = [];

    /**
     * Permite adicionar métodos HTTP customizados.
     *
     * @param  string $method Método HTTP customizado.
     * @return void
     */
    public static function addHttpMethod(string $method): void
    {
        $method = strtoupper($method);
        if (!in_array($method, self::$httpMethodsAccepted)) {
            self::$httpMethodsAccepted[] = $method;
        }
    }

    /**
     * Define um prefixo/base para rotas agrupadas OU registra middlewares para um grupo de rotas.
     *
     * @param  string   $prev_path      Prefixo/base para rotas.
     * @param  callable ...$middlewares Middlewares para o grupo.
     * @throws InvalidArgumentException Se $prev_path não for string.
     * @return void
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
     * Adiciona uma nova rota com método, caminho, middlewares e handler.
     *
     * @param  string   $method         Método
     *                                  HTTP.
     * @param  string   $path           Caminho da rota.
     * @param  callable $handler        Handler da rota.
     * @param  array    $metadata       Metadados da rota.
     * @param  mixed    ...$middlewares Middlewares opcionais.
     * @throws InvalidArgumentException Se o método não for suportado.
     * @return void
     */
    public static function add(
        string $method,
        string $path,
        callable $handler,
        array $metadata = [],
        ...$middlewares
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

        // Corrigir: só aplica o prefixo do grupo atual, não acumula
        $prefix = self::$current_group_prefix;
        if (!empty($prefix) && $prefix !== '/' && strpos($path, $prefix) !== 0) {
            $path = $prefix . $path;
            $path = preg_replace('/\/+/', '/', $path); // Remove duplicate slashes
        }

        // Ensure the path starts with a slash
        if (!empty($path) && $path[0] !== '/') {
            $path = '/' . $path;
        }

        // Adiciona middlewares de grupo se houver para o prefixo
        $groupMiddlewares = [];
        foreach (self::$groupMiddlewares as $prefix => $middlewares) {
            if (!empty($path) && strpos($path, $prefix) === 0) {
                $groupMiddlewares = array_merge($groupMiddlewares, $middlewares);
            }
        }

        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'middlewares' => array_merge($groupMiddlewares, $middlewares),
            'handler' => $handler,
            'metadata' => self::sanitizeForJson($metadata)
        ];
    }

    /**
     * Verifica se array é associativo
     *
     * @param mixed[] $arr
     */
    private static function isAssoc(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Get routes based on method and path.
     *
     * @param  string      $method The HTTP method (GET, POST, etc.).
     * @param  string|null $path   The path to match (optional).
     * @throws InvalidArgumentException if the method is not supported.
     * @return array<string, mixed>|null The matching routes or null if not found.
     */
    public static function identify(string $method, ?string $path = null): ?array
    {
        if (!in_array(strtoupper($method), self::$httpMethodsAccepted)) {
            throw new InvalidArgumentException("Method {$method} is not supported");
        }
        $method = strtoupper($method);
        if (is_null($path)) {
            $path = self::DEFAULT_PATH;
        }

        // Filter routes based on method
        $routes = array_filter(
            self::$routes,
            function ($route) use ($method) {
                return $route['method'] === $method;
            }
        );

        if (empty($routes)) {
            return null; // No routes found for the specified method
        }

        // 1. Tenta encontrar rota estática (exata)
        foreach ($routes as $route) {
            if ($route['path'] === $path) {
                return $route;
            }
        }

        // 2. Tenta encontrar rota dinâmica (com parâmetros)
        foreach ($routes as $route) {
            $pattern = preg_replace('/\/(:[^\/]+)/', '/([^/]+)', $route['path']);
            // Permitir barra final opcional
            $pattern = rtrim($pattern, '/');
            $pattern = '#^' . $pattern . '/?$#';
            if ($route['path'] === self::DEFAULT_PATH) {
                if ($path === self::DEFAULT_PATH) {
                    return $route;
                }
            } elseif (preg_match($pattern, $path)) {
                return $route;
            }
        }
        return null; // Nenhuma rota encontrada
    }

    /**
     * @param mixed[] $args
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
            $output .= sprintf(
                "%s %s => %s\n",
                $route['method'],
                $route['path'],
                is_callable($route['handler']) ? 'Callable' : 'Not Callable'
            );
        }
        return $output;
    }

    /**
     * Retorna todas as rotas registradas (para exportação/documentação).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Retorna os métodos HTTP aceitos.
     *
     * @return array<string>
     */
    public static function getHttpMethodsAccepted(): array
    {
        return self::$httpMethodsAccepted;
    }

    /**
     * Remove closures, objetos e recursos de arrays recursivamente
     */
    private static function sanitizeForJson($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    $out[$k] = self::sanitizeForJson($v);
                } elseif (is_scalar($v) || is_null($v)) {
                    $out[$k] = $v;
                } elseif (is_object($v)) {
                    // Permite stdClass convertendo para array
                    if ($v instanceof \stdClass) {
                        $out[$k] = self::sanitizeForJson((array)$v);
                    } else {
                        // Ignora closures e outros objetos
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
     * Limpa todas as rotas registradas.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$routes = [];
        self::$current_group_prefix = '';
        self::$groupMiddlewares = [];
    }

    /**
     * Registra uma rota para qualquer método HTTP.
     *
     * @param  string $path        Caminho da rota.
     * @param  mixed  ...$handlers Middlewares e handler final.
     * @return void
     */
    public static function any(string $path, ...$handlers): void
    {
        foreach (self::$httpMethodsAccepted as $method) {
            self::add($method, $path, ...$handlers);
        }
    }

    /**
     * Registra múltiplas rotas para os mesmos handlers.
     *
     * @param  array<string> $methods     Métodos
     *                                    HTTP.
     * @param  string        $path        Caminho da rota.
     * @param  mixed         ...$handlers Middlewares e handler final.
     * @return void
     */
    public static function match(array $methods, string $path, ...$handlers): void
    {
        foreach ($methods as $method) {
            self::add($method, $path, ...$handlers);
        }
    }

    /**
     * Cria um grupo de rotas com prefixo e middlewares comuns.
     *
     * @param  string          $prefix      Prefixo das rotas.
     * @param  callable        $callback    Callback que define as rotas do grupo.
     * @param  array<callable> $middlewares Middlewares comuns ao grupo.
     * @return void
     */
    public static function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $previousPrefix = self::$current_group_prefix;

        // Combinar prefixos
        $newPrefix = $previousPrefix . $prefix;
        $newPrefix = preg_replace('/\/+/', '/', $newPrefix); // Remove duplicate slashes

        self::use($newPrefix ?? '', ...$middlewares);

        // Executar callback que define as rotas
        $callback();

        // Restaurar prefixo anterior
        self::$current_group_prefix = $previousPrefix;
    }

    /**
     * Registra uma rota GET
     *
     * @param callable $handler
     */
    public static function get(string $path, $handler, array $metadata = []): void
    {
        self::add('GET', $path, $handler, $metadata);
    }

    /**
     * Registra uma rota POST
     *
     * @param callable $handler
     */
    public static function post(string $path, $handler, array $metadata = []): void
    {
        self::add('POST', $path, $handler, $metadata);
    }

    /**
     * Registra uma rota PUT
     *
     * @param callable $handler
     */
    public static function put(string $path, $handler, array $metadata = []): void
    {
        self::add('PUT', $path, $handler, $metadata);
    }

    /**
     * Registra uma rota DELETE
     *
     * @param callable $handler
     */
    public static function delete(string $path, $handler, array $metadata = []): void
    {
        self::add('DELETE', $path, $handler, $metadata);
    }

    /**
     * Registra uma rota PATCH
     *
     * @param callable $handler
     */
    public static function patch(string $path, $handler, array $metadata = []): void
    {
        self::add('PATCH', $path, $handler, $metadata);
    }

    /**
     * Registra uma rota OPTIONS
     *
     * @param callable $handler
     */
    public static function options(string $path, $handler, array $metadata = []): void
    {
        self::add('OPTIONS', $path, $handler, $metadata);
    }

    /**
     * Registra uma rota HEAD
     *
     * @param callable $handler
     */
    public static function head(string $path, $handler, array $metadata = []): void
    {
        self::add('HEAD', $path, $handler, $metadata);
    }
}
