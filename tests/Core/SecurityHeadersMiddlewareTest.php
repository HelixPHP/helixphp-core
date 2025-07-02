<?php

namespace Express\Tests\Core;

use PHPUnit\Framework\TestCase;
use Express\Http\Psr15\Middleware\SecurityHeadersMiddleware;
use Express\Http\Psr7\ServerRequest;
use Express\Http\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testSecurityHeadersAreSet(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = new ServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $response = $middleware->process($request, $handler);
        $this->assertNotEmpty($response->getHeaderLine('X-Frame-Options'));
        $this->assertNotEmpty($response->getHeaderLine('X-Content-Type-Options'));
        $this->assertNotEmpty($response->getHeaderLine('X-XSS-Protection'));
    }
}
