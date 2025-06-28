<?php

declare(strict_types=1);

namespace Express\Http\Psr15\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * High-Performance CORS Middleware
 *
 * Optimized CORS implementation with minimal overhead
 */
class HighPerformanceCorsMiddleware implements MiddlewareInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config + [
            'origins' => ['*'],
            'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'credentials' => false,
            'max_age' => 3600
        ];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();

        // Handle preflight quickly
        if ($method === 'OPTIONS') {
            return $this->createPreflightResponse($request);
        }

        // Process request normally
        $response = $handler->handle($request);

        // Add CORS headers efficiently
        return $this->addCorsHeaders($response, $request);
    }

    private function createPreflightResponse(ServerRequestInterface $request): ResponseInterface
    {
        $factory = new \Express\Http\Psr7\Factory\HighPerformanceResponseFactory();
        $response = $factory->createResponse(204);

        return $this->addCorsHeaders($response, $request);
    }

    private function addCorsHeaders(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        // Fast origin check
        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin ?: '*');
        }

        // Add other headers efficiently
        $response = $response
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->config['methods']))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->config['headers']))
            ->withHeader('Access-Control-Max-Age', (string) $this->config['max_age']);

        if ($this->config['credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    private function isOriginAllowed(string $origin): bool
    {
        if (empty($origin)) {
            return true;
        }

        $allowedOrigins = $this->config['origins'];

        // Fast wildcard check
        if (in_array('*', $allowedOrigins, true)) {
            return true;
        }

        // Direct match check
        return in_array($origin, $allowedOrigins, true);
    }
}
