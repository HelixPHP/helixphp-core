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
        $method = $request->getMethod();

        // Handle preflight quickly (optimized)
        if ($method === 'OPTIONS') {
            return $this->handlePreflightOptimized($request);
        }

        // Process request normally
        $response = $handler->handle($request);

        // Add CORS headers efficiently
        return $this->addCorsHeadersOptimized($request, $response);
    }

    private function handlePreflightOptimized(ServerRequestInterface $request): ResponseInterface
    {
        $factory = new \Express\Http\Psr7\Factory\ResponseFactory();
        $response = $factory->createResponse(200);

        // Build headers array for efficient processing
        $headers = [
            'Access-Control-Allow-Origin' => $this->getAllowedOrigin($request),
            'Access-Control-Allow-Methods' => implode(', ', $this->config['methods']),
            'Access-Control-Allow-Headers' => implode(', ', $this->config['headers']),
            'Access-Control-Max-Age' => (string) $this->config['max_age']
        ];

        if ($this->config['credentials']) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        // Apply all headers at once
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    private function addCorsHeadersOptimized(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Build headers array for efficient processing
        $headers = [
            'Access-Control-Allow-Origin' => $this->getAllowedOrigin($request)
        ];

        if ($this->config['credentials']) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        if (!empty($this->config['expose_headers'])) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $this->config['expose_headers']);
        }

        // Apply all headers efficiently
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
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

    /**
     * Handle preflight requests (legacy method for compatibility)
     *
     * @deprecated Use handlePreflightOptimized for better performance
     */
    private function handlePreflight(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handlePreflightOptimized($request);
    }

    /**
     * Add CORS headers to response (legacy method for compatibility)
     *
     * @deprecated Use addCorsHeadersOptimized for better performance
     */
    private function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->addCorsHeadersOptimized($request, $response);
    }
}
