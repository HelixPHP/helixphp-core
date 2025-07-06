<?php

declare(strict_types=1);

namespace Helix\Http\Psr15;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Abstract base middleware class that implements PSR-15 MiddlewareInterface
 */
abstract class AbstractMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to the provided request handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Before processing hook
        $request = $this->before($request);

        // Check if we should continue to next middleware
        if (!$this->shouldContinue($request)) {
            return $this->getResponse($request);
        }

        // Continue to next middleware/handler
        $response = $handler->handle($request);

        // After processing hook
        return $this->after($request, $response);
    }

    /**
     * Hook called before processing continues to next middleware
     */
    protected function before(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request;
    }

    /**
     * Hook called after next middleware/handler processes request
     */
    protected function after(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    /**
     * Determine if processing should continue to next middleware
     */
    protected function shouldContinue(ServerRequestInterface $request): bool
    {
        return true;
    }

    /**
     * Get response when processing should not continue
     */
    protected function getResponse(ServerRequestInterface $request): ResponseInterface
    {
        return new \Helix\Http\Psr7\Response(
            200,
            [],
            \Helix\Http\Psr7\Stream::createFromString('')
        );
    }
}
