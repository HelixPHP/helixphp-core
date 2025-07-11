<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PivotPHP\Core\Http\Psr7\Response;
use PivotPHP\Core\Http\Psr7\Stream;
use Throwable;

/**
 * Error Handling Middleware
 *
 * Catches and handles exceptions that occur during request processing,
 * providing appropriate error responses and logging.
 *
 * @package PivotPHP\Core\Middleware\Http
 * @since 1.1.2
 */
class ErrorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            $body = [
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
            $stream = Stream::createFromString((string)json_encode($body, JSON_UNESCAPED_UNICODE));
            return (new Response(500))
                ->withHeader('Content-Type', 'application/json')
                ->withBody($stream);
        }
    }
}
