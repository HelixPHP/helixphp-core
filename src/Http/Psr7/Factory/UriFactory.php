<?php

declare(strict_types=1);

namespace Helix\Http\Psr7\Factory;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Helix\Http\Psr7\Uri;

/**
 * PSR-17 URI Factory implementation
 */
class UriFactory implements UriFactoryInterface
{
    /**
     * Create a new URI.
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
