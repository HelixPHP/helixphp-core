<?php
namespace Express\Controller;

use InvalidArgumentException;

/**
 * RouterInstance permite criar sub-routers independentes com prefixo e rotas próprias.
 */
class RouterInstance
{
    private string $prefix;
    private array $routes = [];
    private array $middlewares = [];

    public function __construct(string $prefix = '/')
    {
        $this->prefix = $prefix;
    }

    public function use(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function get(string $path, callable|array ...$handlers): void
    {
        $this->add('GET', $path, ...$handlers);
    }
    public function post(string $path, callable|array ...$handlers): void
    {
        $this->add('POST', $path, ...$handlers);
    }
    public function put(string $path, callable|array ...$handlers): void
    {
        $this->add('PUT', $path, ...$handlers);
    }
    public function delete(string $path, callable|array ...$handlers): void
    {
        $this->add('DELETE', $path, ...$handlers);
    }
    public function patch(string $path, callable|array ...$handlers): void
    {
        $this->add('PATCH', $path, ...$handlers);
    }
    public function options(string $path, callable|array ...$handlers): void
    {
        $this->add('OPTIONS', $path, ...$handlers);
    }
    public function head(string $path, callable|array ...$handlers): void
    {
        $this->add('HEAD', $path, ...$handlers);
    }

    // Suporte a métodos HTTP customizados (ex: custom, trace, etc)
    public function __call(string $method, array $args): void
    {
        $httpMethod = strtoupper($method);
        $path = $args[0] ?? '/';
        $handlers = array_slice($args, 1);
        $this->add($httpMethod, $path, ...$handlers);
    }

    private function add(string $method, string $path, callable|array ...$handlers): void
    {
        if (empty($path)) {
            $path = '/';
        }
        $fullPath = rtrim($this->prefix, '/') . '/' . ltrim($path, '/');
        $fullPath = preg_replace('/\/+/', '/', $fullPath);
        $metadata = [];
        if (is_array(end($handlers)) && $this->isAssoc(end($handlers))) {
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

    private function isAssoc(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Retorna as rotas deste sub-router.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
