<?php

declare(strict_types=1);

namespace Express\Http\Psr7\Factory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Express\Http\Psr7\Response;
use Express\Http\Psr7\Stream;
use Express\Http\Psr7\Pool\ResponsePool;

/**
 * PSR-17 Response Factory implementation with object pooling optimization
 */
class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create a new response using object pool
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        // Use object pool for better performance
        return ResponsePool::getResponse($code);
    }

    /**
     * Create response with JSON body (optimized)
     */
    public function createJsonResponse(array $data, int $code = 200): ResponseInterface
    {
        return ResponsePool::getJsonResponse($data, $code);
    }

    /**
     * Create response with text body (optimized)
     */
    public function createTextResponse(string $text, int $code = 200): ResponseInterface
    {
        return ResponsePool::getTextResponse($text, $code);
    }

    /**
     * Create response with HTML body (optimized)
     */
    public function createHtmlResponse(string $html, int $code = 200): ResponseInterface
    {
        return ResponsePool::getHtmlResponse($html, $code);
    }

    /**
     * Create legacy response without pooling (for compatibility)
     */
    public function createResponseLegacy(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        return new Response($code, [], $stream, '1.1', $reasonPhrase);
    }

    /**
     * Warm up the response pool
     */
    public static function warmUp(): void
    {
        ResponsePool::warmUp();
    }

    /**
     * Get pool statistics
     */
    public static function getPoolStats(): array
    {
        return ResponsePool::getStats();
    }
}
