<?php

declare(strict_types=1);

namespace Helix\Http\Psr7\Factory;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Helix\Http\Psr7\ServerRequest;

/**
 * PSR-17 Server Request Factory implementation
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * Create a new server request.
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, null, [], '1.1', $serverParams);
    }
}
