<?php

declare(strict_types=1);

namespace Express\Http\Psr7\Factory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Express\Http\Psr7\Response;
use Express\Http\Psr7\OptimizedStream;

/**
 * High-Performance Response Factory
 *
 * Optimized factory for scenarios where maximum performance is required
 */
class HighPerformanceResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        // Use optimized stream for better performance
        $stream = new OptimizedStream(fopen('php://temp', 'r+'));
        return new Response($code, [], $stream, '1.1', $reasonPhrase);
    }

    /**
     * Create response with JSON body (optimized)
     */
    public function createJsonResponse(array $data, int $code = 200): ResponseInterface
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $stream = OptimizedStream::createFromString($json);

        return new Response(
            $code,
            ['Content-Type' => 'application/json'],
            $stream
        );
    }

    /**
     * Create response with text body (optimized)
     */
    public function createTextResponse(string $text, int $code = 200): ResponseInterface
    {
        $stream = OptimizedStream::createFromString($text);

        return new Response(
            $code,
            ['Content-Type' => 'text/plain'],
            $stream
        );
    }
}
