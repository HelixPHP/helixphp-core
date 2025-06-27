<?php

declare(strict_types=1);

namespace Express\Http\Psr15\Middleware;

use Express\Http\Psr15\AbstractMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 CORS Middleware
 */
class CorsMiddleware extends AbstractMiddleware
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'origin' => '*',
            'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'credentials' => false,
            'max_age' => 86400, // 24 hours
            'expose_headers' => [],
        ], $config);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflight($request);
        }

        // Process request normally
        $response = $handler->handle($request);

        // Add CORS headers to response
        return $this->addCorsHeaders($request, $response);
    }

    private function handlePreflight(ServerRequestInterface $request): ResponseInterface
    {
        $response = new \Express\Http\Psr7\Response(200);

        // Add preflight headers
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $this->getAllowedOrigin($request))
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->config['methods']))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->config['headers']))
            ->withHeader('Access-Control-Max-Age', (string) $this->config['max_age']);

        if ($this->config['credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    private function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $this->getAllowedOrigin($request));

        if ($this->config['credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($this->config['expose_headers'])) {
            $response = $response->withHeader(
                'Access-Control-Expose-Headers',
                implode(', ', $this->config['expose_headers'])
            );
        }

        return $response;
    }

    private function getAllowedOrigin(ServerRequestInterface $request): string
    {
        $origin = $request->getHeaderLine('Origin');

        if ($this->config['origin'] === '*') {
            return '*';
        }

        if (is_array($this->config['origin'])) {
            return in_array($origin, $this->config['origin']) ? $origin : 'null';
        }

        return $this->config['origin'];
    }
}
