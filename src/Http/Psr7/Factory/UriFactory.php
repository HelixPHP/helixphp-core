<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Factory;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use PivotPHP\Core\Http\Psr7\Uri;

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
