<?php

declare(strict_types=1);

namespace Express\Http\Psr7\Factory;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Express\Http\Psr7\Request;
use Express\Http\Psr7\Uri;
use Express\Http\Psr7\Stream;

/**
 * PSR-17 Request Factory implementation
 */
class RequestFactory implements RequestFactoryInterface
{
    /**
     * Create a new request.
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        return new Request($method, $uri, Stream::createFromString(''));
    }
}
