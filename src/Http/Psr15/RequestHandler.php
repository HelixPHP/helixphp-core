<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr15;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * PSR-15 compatible request handler that processes middleware stack
 */
class RequestHandler implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];
    private ?RequestHandlerInterface $fallbackHandler = null;
    private int $index = 0;

    public function __construct(?RequestHandlerInterface $fallbackHandler = null)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    /**
     * Add middleware to the stack
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Handle the request by processing through middleware stack
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // If we've processed all middlewares, delegate to fallback handler
        if ($this->index >= count($this->middlewares)) {
            if ($this->fallbackHandler !== null) {
                return $this->fallbackHandler->handle($request);
            }

            // Default response if no fallback handler
            return new \PivotPHP\Core\Http\Psr7\Response(
                404,
                [],
                \PivotPHP\Core\Http\Psr7\Stream::createFromString('Not Found')
            );
        }

        $middleware = $this->middlewares[$this->index++];
        return $middleware->process($request, $this);
    }

    /**
     * Reset the handler index for reuse
     */
    public function reset(): self
    {
        $this->index = 0;
        return $this;
    }
}
