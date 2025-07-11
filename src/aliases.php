<?php

/**
 * Aliases de compatibilidade v1.1.2 - PivotPHP Core
 *
 * Estes aliases mantêm compatibilidade com a estrutura anterior
 * Remover em versão futura (v1.2.0)
 *
 * @package PivotPHP\Core
 */

declare(strict_types=1);

// Middleware HTTP
class_alias(
    'PivotPHP\Core\Middleware\Http\CorsMiddleware',
    'PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware'
);
class_alias(
    'PivotPHP\Core\Middleware\Http\ErrorMiddleware',
    'PivotPHP\Core\Http\Psr15\Middleware\ErrorMiddleware'
);

// Middleware de Segurança
class_alias(
    'PivotPHP\Core\Middleware\Security\CsrfMiddleware',
    'PivotPHP\Core\Http\Psr15\Middleware\CsrfMiddleware'
);
class_alias(
    'PivotPHP\Core\Middleware\Security\XssMiddleware',
    'PivotPHP\Core\Http\Psr15\Middleware\XssMiddleware'
);
class_alias(
    'PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware',
    'PivotPHP\Core\Http\Psr15\Middleware\SecurityHeadersMiddleware'
);
class_alias(
    'PivotPHP\Core\Middleware\Security\AuthMiddleware',
    'PivotPHP\Core\Http\Psr15\Middleware\AuthMiddleware'
);

// Middleware de Performance
class_alias(
    'PivotPHP\Core\Middleware\Performance\RateLimitMiddleware',
    'PivotPHP\Core\Http\Psr15\Middleware\RateLimitMiddleware'
);
class_alias(
    'PivotPHP\Core\Middleware\Performance\CacheMiddleware',
    'PivotPHP\Core\Http\Psr15\Middleware\CacheMiddleware'
);

// Performance e Pool
class_alias(
    'PivotPHP\Core\Performance\PerformanceMonitor',
    'PivotPHP\Core\Monitoring\PerformanceMonitor'
);
class_alias(
    'PivotPHP\Core\Http\Pool\DynamicPoolManager',
    'PivotPHP\Core\Http\Psr7\Pool\DynamicPoolManager'
);
class_alias(
    'PivotPHP\Core\Http\Pool\DynamicPoolManager',
    'PivotPHP\Core\Http\Pool\DynamicPool'
);

// Core Classes - Compatibilidade
class_alias(
    'PivotPHP\Core\Core\Application',
    'PivotPHP\Core\Application'
);

// Utilitários
class_alias(
    'PivotPHP\Core\Utils\Arr',
    'PivotPHP\Core\Support\Arr'
);
