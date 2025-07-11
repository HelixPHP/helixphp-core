<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Security;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Security Headers Middleware
 *
 * Adds essential security headers to HTTP responses including
 * X-Frame-Options, X-Content-Type-Options, and X-XSS-Protection.
 *
 * @package PivotPHP\Core\Middleware\Security
 * @since 1.1.2
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Process the request and add security headers to the response
     *
     * @param ServerRequestInterface $request The incoming request
     * @param RequestHandlerInterface $handler The request handler
     * @return ResponseInterface The response with security headers
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        return $response
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-XSS-Protection', '1; mode=block');
    }

    /**
     * Create a new instance of the middleware
     *
     * @param array $options Configuration options (currently unused)
     * @return self New middleware instance
     */
    public static function create(array $options = []): self
    {
        return new self();
    }

    /**
     * Strict method
     */
    public static function strict(array $options = []): self
    {
        return new self();
    }

    /**
     * CsrfOnly method
     */
    public static function csrfOnly(array $options = []): self
    {
        return new self();
    }

    /**
     * XssOnly method
     */
    public static function xssOnly(array $options = []): self
    {
        return new self();
    }

    /**
     * __construct method
     */
    public function __construct()
    {
    }
}
