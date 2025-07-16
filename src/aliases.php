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

// Performance e Pool - Legacy Support
class_alias(
    'PivotPHP\Core\Performance\PerformanceMonitor',
    'PivotPHP\Core\Monitoring\PerformanceMonitor'
);
class_alias(
    'PivotPHP\Core\Http\Pool\PoolManager',
    'PivotPHP\Core\Http\Psr7\Pool\DynamicPoolManager'
);
class_alias(
    'PivotPHP\Core\Http\Pool\PoolManager',
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

// Legacy Classes - Backward Compatibility for v1.2.0
// These aliases allow old code to continue working while using new implementations

// Performance Classes - Legacy Aliases
class_alias(
    'PivotPHP\Core\Performance\PerformanceMode',
    'PivotPHP\Core\Performance\SimplePerformanceMode'
);
class_alias(
    'PivotPHP\Core\Legacy\Performance\HighPerformanceMode',
    'PivotPHP\Core\Performance\HighPerformanceMode'
);

// Middleware - Legacy Aliases
class_alias(
    'PivotPHP\Core\Middleware\LoadShedder',
    'PivotPHP\Core\Middleware\SimpleLoadShedder'
);
class_alias(
    'PivotPHP\Core\Legacy\Middleware\TrafficClassifier',
    'PivotPHP\Core\Middleware\TrafficClassifier'
);

// Memory Management - Legacy Aliases
class_alias(
    'PivotPHP\Core\Memory\MemoryManager',
    'PivotPHP\Core\Memory\SimpleMemoryManager'
);

// Pool Management - Legacy Aliases
class_alias(
    'PivotPHP\Core\Http\Pool\PoolManager',
    'PivotPHP\Core\Http\Pool\SimplePoolManager'
);
class_alias(
    'PivotPHP\Core\Http\Pool\PoolManager',
    'PivotPHP\Core\Http\Pool\DynamicPoolManager'
);

// Performance Monitoring - Legacy Aliases
class_alias(
    'PivotPHP\Core\Performance\PerformanceMonitor',
    'PivotPHP\Core\Performance\SimplePerformanceMonitor'
);

// JSON Pool - Legacy Aliases
class_alias(
    'PivotPHP\Core\Json\Pool\JsonBufferPool',
    'PivotPHP\Core\Json\Pool\SimpleJsonBufferPool'
);

// Event System - Legacy Aliases
class_alias(
    'PivotPHP\Core\Events\EventDispatcher',
    'PivotPHP\Core\Events\SimpleEventDispatcher'
);

// Deprecated Utils - Simple Implementations
class_alias(
    'PivotPHP\Core\Utils\SerializationCache',
    'PivotPHP\Core\Legacy\Utils\SerializationCache'
);
class_alias(
    'PivotPHP\Core\Utils\OpenApiExporter',
    'PivotPHP\Core\Legacy\Utils\OpenApiExporter'
);
class_alias(
    'PivotPHP\Core\Providers\ExtensionManager',
    'PivotPHP\Core\Legacy\Providers\ExtensionManager'
);

// API Documentation - Core Feature
class_alias(
    'PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware',
    'PivotPHP\Core\Middleware\ApiDocumentationMiddleware'
);
