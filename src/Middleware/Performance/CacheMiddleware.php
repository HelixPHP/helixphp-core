<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Performance;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Cache Middleware
 *
 * Provides HTTP response caching to improve application performance
 * by storing and serving cached responses for repeated requests.
 *
 * @package PivotPHP\Core\Middleware\Performance
 * @since 1.1.2
 */
class CacheMiddleware implements MiddlewareInterface
{
    private int $ttl;
    private string $cacheDir;

    /**
     * __construct method
     */
    public function __construct(int $ttl = 300, string $cacheDir = '/tmp/expressphp_cache')
    {
        $this->ttl = $ttl;
        $this->cacheDir = $cacheDir;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $this->generateCacheKey($request);
        $cacheFile = $this->cacheDir . '/' . $key;

        if (file_exists($cacheFile) && (filemtime($cacheFile) + $this->ttl) > time()) {
            $cached = file_get_contents($cacheFile);
            $response = unserialize((string)$cached);
            if ($response instanceof ResponseInterface) {
                return $response;
            }
        }

        $response = $handler->handle($request);
        // Adiciona header de cache-control
        if (method_exists($response, 'withHeader')) {
            $response = $response->withHeader('Cache-Control', 'public, max-age=' . $this->ttl);
        }
        file_put_contents($cacheFile, serialize($response));
        return $response;
    }

    private function generateCacheKey(ServerRequestInterface $request): string
    {
        $uri = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery();
        $method = $request->getMethod();
        return md5($method . ':' . $uri . '?' . $query);
    }
}
