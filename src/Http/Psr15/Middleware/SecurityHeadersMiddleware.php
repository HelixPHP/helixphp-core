<?php

declare(strict_types=1);

namespace Express\Http\Psr15\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        return $response
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-XSS-Protection', '1; mode=block');
    }

    public static function create(array $options = []): self
    {
        return new self();
    }

    public static function strict(array $options = []): self
    {
        return new self();
    }

    public static function csrfOnly(array $options = []): self
    {
        return new self();
    }

    public static function xssOnly(array $options = []): self
    {
        return new self();
    }

    public function __construct()
    {
    }
}
