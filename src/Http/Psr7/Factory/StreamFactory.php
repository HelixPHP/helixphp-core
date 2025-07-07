<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Factory;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use PivotPHP\Core\Http\Psr7\Stream;

/**
 * PSR-17 Stream Factory implementation
 */
class StreamFactory implements StreamFactoryInterface
{
    /**
     * Create a new stream from a string.
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::createFromString($content);
    }

    /**
     * Create a stream from an existing file.
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return Stream::createFromFile($filename, $mode);
    }

    /**
     * Create a new stream from an existing resource.
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}
