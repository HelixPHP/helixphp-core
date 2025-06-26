<?php

/**
 * Arquivo de conveniência para importação fácil dos middlewares de segurança
 * Express PHP Framework
 */

// Middlewares de Segurança
require_once __DIR__ . '/Security/CsrfMiddleware.php';
require_once __DIR__ . '/Security/XssMiddleware.php';
require_once __DIR__ . '/Security/SecurityMiddleware.php';
require_once __DIR__ . '/Security/AuthMiddleware.php';

// Middlewares Core
require_once __DIR__ . '/Core/AttachmentMiddleware.php';
require_once __DIR__ . '/Core/CorsMiddleware.php';
require_once __DIR__ . '/Core/ErrorHandlerMiddleware.php';
require_once __DIR__ . '/Core/OpenApiDocsMiddleware.php';
require_once __DIR__ . '/Core/RateLimitMiddleware.php';
require_once __DIR__ . '/Core/RequestValidationMiddleware.php';

// Aliases para compatibilidade com versões anteriores
use Express\Middlewares\Security\CsrfMiddleware;
use Express\Middlewares\Security\XssMiddleware;
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Security\AuthMiddleware;
use Express\Middlewares\Core\AttachmentMiddleware;
use Express\Middlewares\Core\CorsMiddleware;
use Express\Middlewares\Core\ErrorHandlerMiddleware;
use Express\Middlewares\Core\OpenApiDocsMiddleware;
use Express\Middlewares\Core\RateLimitMiddleware;
use Express\Middlewares\Core\RequestValidationMiddleware;

// Mapeamento para compatibilidade (opcional)
if (!class_exists('Express\SRC\Services\CsrfMiddleware')) {
    class_alias('Express\Middlewares\Security\CsrfMiddleware', 'Express\SRC\Services\CsrfMiddleware');
}

if (!class_exists('Express\SRC\Services\XssMiddleware')) {
    class_alias('Express\Middlewares\Security\XssMiddleware', 'Express\SRC\Services\XssMiddleware');
}

if (!class_exists('Express\SRC\Services\SecurityMiddleware')) {
    class_alias('Express\Middlewares\Security\SecurityMiddleware', 'Express\SRC\Services\SecurityMiddleware');
}

if (!class_exists('Express\SRC\Services\CorsMiddleware')) {
    class_alias('Express\Middlewares\Core\CorsMiddleware', 'Express\SRC\Services\CorsMiddleware');
}

if (!class_exists('Express\SRC\Services\AttachmentMiddleware')) {
    class_alias('Express\Middlewares\Core\AttachmentMiddleware', 'Express\SRC\Services\AttachmentMiddleware');
}

if (!class_exists('Express\SRC\Services\ErrorHandlerMiddleware')) {
    class_alias('Express\Middlewares\Core\ErrorHandlerMiddleware', 'Express\SRC\Services\ErrorHandlerMiddleware');
}

if (!class_exists('Express\SRC\Services\OpenApiDocsMiddleware')) {
    class_alias('Express\Middlewares\Core\OpenApiDocsMiddleware', 'Express\SRC\Services\OpenApiDocsMiddleware');
}

if (!class_exists('Express\SRC\Services\RateLimitMiddleware')) {
    class_alias('Express\Middlewares\Core\RateLimitMiddleware', 'Express\SRC\Services\RateLimitMiddleware');
}

if (!class_exists('Express\SRC\Services\AuthMiddleware')) {
    class_alias('Express\Middlewares\Security\AuthMiddleware', 'Express\SRC\Services\AuthMiddleware');
}

if (!class_exists('Express\SRC\Services\RequestValidationMiddleware')) {
    class_alias(
        'Express\Middlewares\Core\RequestValidationMiddleware',
        'Express\SRC\Services\RequestValidationMiddleware'
    );
}
