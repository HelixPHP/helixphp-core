<?php

declare(strict_types=1);

namespace PivotPHP\Core\Routing;

/**
 * Mock Request para capturar responses estáticas
 */
class MockRequest
{
    /**
     * @param mixed $default
     * @return mixed
     */
    public function param(string $name, $default = null)
    {
        return $default;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $default;
    }

    /**
     * @param array<mixed> $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return null;
    }
}
