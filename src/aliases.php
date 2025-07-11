<?php

/**
 * Aliases e funções globais para PivotPHP.
 */

// Função global para criação rápida de aplicações (estilo Express.js)
if (!function_exists('express')) {
    /**
     * Cria uma nova instância da aplicação PivotPHP.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return PivotPHP\Core\Core\Application
     */
    function express(?string $basePath = null): PivotPHP\Core\Core\Application
    {
        return PivotPHP\Core\Core\Application::express($basePath);
    }
}

// Helper para criação de aplicação
if (!function_exists('app')) {
    /**
     * Cria uma nova instância da aplicação.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return PivotPHP\Core\Core\Application
     */
    function app(?string $basePath = null): PivotPHP\Core\Core\Application
    {
        return PivotPHP\Core\Core\Application::create($basePath);
    }
}

// Aliases de compatibilidade v1.1.2 - Middlewares
// Estes aliases mantêm compatibilidade com a estrutura anterior
// Remover em versão futura (v1.2.0)

class_alias('PivotPHP\Core\Middleware\Http\CorsMiddleware', 'PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware');
class_alias('PivotPHP\Core\Middleware\Security\CsrfMiddleware', 'PivotPHP\Core\Http\Psr15\Middleware\CsrfMiddleware');
class_alias('PivotPHP\Core\Middleware\Security\XssMiddleware', 'PivotPHP\Core\Http\Psr15\Middleware\XssMiddleware');
class_alias('PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware', 'PivotPHP\Core\Http\Psr15\Middleware\SecurityHeadersMiddleware');
class_alias('PivotPHP\Core\Middleware\Performance\RateLimitMiddleware', 'PivotPHP\Core\Http\Psr15\Middleware\RateLimitMiddleware');
class_alias('PivotPHP\Core\Middleware\Performance\CacheMiddleware', 'PivotPHP\Core\Http\Psr15\Middleware\CacheMiddleware');
class_alias('PivotPHP\Core\Middleware\Http\ErrorMiddleware', 'PivotPHP\Core\Http\Psr15\Middleware\ErrorMiddleware');
class_alias('PivotPHP\Core\Middleware\Security\AuthMiddleware', 'PivotPHP\Core\Http\Psr15\Middleware\AuthMiddleware');
class_alias('PivotPHP\Core\Performance\PerformanceMonitor', 'PivotPHP\Core\Monitoring\PerformanceMonitor');
class_alias('PivotPHP\Core\Http\Pool\DynamicPoolManager', 'PivotPHP\Core\Http\Psr7\Pool\DynamicPoolManager');
class_alias('PivotPHP\Core\Http\Pool\DynamicPoolManager', 'PivotPHP\Core\Http\Pool\DynamicPool');
class_alias('PivotPHP\Core\Utils\Arr', 'PivotPHP\Core\Support\Arr');
