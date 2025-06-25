<?php
namespace Express\Controller;

use InvalidArgumentException;

/**
 * RouterInstance permite criar sub-routers independentes com prefixo e rotas próprias.
 */
class RouterInstance
{
    private $prefix;
    private $routes = [];
    private $middlewares = [];

    public function __construct($prefix = '/')
    {
        $this->prefix = $prefix;
    }

    public function use($middleware)
    {
        if (!is_callable($middleware)) {
            throw new InvalidArgumentException('Middleware must be callable');
        }
        $this->middlewares[] = $middleware;
    }

    public function get($path, ...$handlers) { $this->add('GET', $path, ...$handlers); }
    public function post($path, ...$handlers) { $this->add('POST', $path, ...$handlers); }
    public function put($path, ...$handlers) { $this->add('PUT', $path, ...$handlers); }
    public function delete($path, ...$handlers) { $this->add('DELETE', $path, ...$handlers); }
    public function patch($path, ...$handlers) { $this->add('PATCH', $path, ...$handlers); }
    public function options($path, ...$handlers) { $this->add('OPTIONS', $path, ...$handlers); }
    public function head($path, ...$handlers) { $this->add('HEAD', $path, ...$handlers); }

    // Suporte a métodos HTTP customizados (ex: custom, trace, etc)
    public function __call($method, $args)
    {
        $httpMethod = strtoupper($method);
        $path = $args[0] ?? '/';
        $handlers = array_slice($args, 1);
        $this->add($httpMethod, $path, ...$handlers);
    }

    private function add($method, $path, ...$handlers)
    {
        if (empty($path)) $path = '/';
        $fullPath = rtrim($this->prefix, '/') . '/' . ltrim($path, '/');
        $fullPath = preg_replace('/\/+/', '/', $fullPath);
        $metadata = [];
        if (is_array(end($handlers)) && $this->isAssoc(end($handlers))) {
            $metadata = array_pop($handlers);
        }
        $handler = array_pop($handlers);
        foreach ($handlers as $mw) {
            if (!is_callable($mw)) throw new InvalidArgumentException('Middleware must be callable');
        }
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'middlewares' => array_merge($this->middlewares, $handlers),
            'handler' => $handler,
            'metadata' => $metadata
        ];
    }

    private function isAssoc(array $arr) {
        if ([] === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Retorna as rotas deste sub-router.
     */
    public function getRoutes() { return $this->routes; }
}
