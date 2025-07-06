<?php

namespace Helix\Routing;

use InvalidArgumentException;
use Helix\Utils\Arr;

/**
 * RouterInstance permite criar sub-routers independentes com prefixo e rotas próprias.
 */
class RouterInstance
{
    private string $prefix;
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $routes = [];
    /**
     * @var callable[]
     */
    private array $middlewares = [];

    public function __construct(string $prefix = '/')
    {
        $this->prefix = $prefix;
    }

    public function use(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @param string $path
     * @param mixed  ...$handlers
     */
    public function get(string $path, ...$handlers): void
    {
        $this->add('GET', $path, ...$handlers);
    }

    /**
     * @param string $path
     * @param mixed  ...$handlers
     */
    public function post(string $path, ...$handlers): void
    {
        $this->add('POST', $path, ...$handlers);
    }

    /**
     * @param string $path
     * @param mixed  ...$handlers
     */
    public function put(string $path, ...$handlers): void
    {
        $this->add('PUT', $path, ...$handlers);
    }

    /**
     * @param string $path
     * @param mixed  ...$handlers
     */
    public function delete(string $path, ...$handlers): void
    {
        $this->add('DELETE', $path, ...$handlers);
    }

    /**
     * @param string $path
     * @param mixed  ...$handlers
     */
    public function patch(string $path, ...$handlers): void
    {
        $this->add('PATCH', $path, ...$handlers);
    }

    /**
     * @param string $path
     * @param mixed  ...$handlers
     */
    public function options(string $path, ...$handlers): void
    {
        $this->add('OPTIONS', $path, ...$handlers);
    }

    /**
     * @param string $path
     * @param mixed  ...$handlers
     */
    public function head(string $path, ...$handlers): void
    {
        $this->add('HEAD', $path, ...$handlers);
    }

    /**
     * Registra uma rota para qualquer método HTTP.
     *
     * @param string $path
     * @param mixed  ...$handlers
     */
    public function any(string $path, ...$handlers): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
        foreach ($methods as $method) {
            $this->add($method, $path, ...$handlers);
        }
    }

    /**
     * Registra múltiplas rotas para os mesmos handlers.
     *
     * @param array<string> $methods
     * @param string        $path
     * @param mixed         ...$handlers
     */
    public function match(array $methods, string $path, ...$handlers): void
    {
        foreach ($methods as $method) {
            $this->add($method, $path, ...$handlers);
        }
    }

    /**
     * Suporte a métodos HTTP customizados
     *
     * @param mixed[] $args
     */
    public function __call(string $method, array $args): void
    {
        $httpMethod = strtoupper($method);
        $path = $args[0] ?? '/';
        $handlers = array_slice($args, 1);
        $this->add($httpMethod, $path, ...$handlers);
    }

    /**
     * @param string $method
     * @param string $path
     * @param mixed  ...$handlers
     */
    private function add(string $method, string $path, ...$handlers): void
    {
        if (empty($path)) {
            $path = '/';
        }

        $fullPath = rtrim($this->prefix, '/') . '/' . ltrim($path, '/');
        $fullPath = preg_replace('/\/+/', '/', $fullPath);

        $metadata = [];
        if (is_array(end($handlers)) && Arr::isAssoc(end($handlers))) {
            $metadata = array_pop($handlers);
        }

        $handler = array_pop($handlers);
        if (!is_callable($handler)) {
            throw new InvalidArgumentException('Handler must be a callable function');
        }

        foreach ($handlers as $mw) {
            if (!is_callable($mw)) {
                throw new InvalidArgumentException('Middleware must be callable');
            }
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'middlewares' => array_merge($this->middlewares, $handlers),
            'handler' => $handler,
            'metadata' => $metadata
        ];
    }

    /**
     * Retorna as rotas deste sub-router.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Obtém o prefixo do router.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Cria um grupo de rotas com prefixo adicional.
     *
     * @param  string   $prefix
     * @param  callable $callback
     * @return void
     */
    public function group(string $prefix, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $this->prefix = rtrim($this->prefix, '/') . '/' . ltrim($prefix, '/');
        $this->prefix = preg_replace('/\/+/', '/', $this->prefix) ?? $this->prefix;

        $callback($this);

        $this->prefix = $previousPrefix;
    }

    /**
     * Limpa todas as rotas e middlewares.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->routes = [];
        $this->middlewares = [];
    }
}
