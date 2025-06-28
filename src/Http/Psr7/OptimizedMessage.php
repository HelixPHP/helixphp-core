<?php

declare(strict_types=1);

namespace Express\Http\Psr7;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Optimized HTTP message implementation (PSR-7)
 *
 * Performance-optimized version with reduced validation overhead
 * for high-performance scenarios where input is trusted.
 *
 * @package Express\Http\Psr7
 * @since 2.1.0
 */
class OptimizedMessage implements MessageInterface
{
    protected string $protocolVersion = '1.1';
    protected array $headers = [];
    protected array $headerNames = [];
    protected StreamInterface $body;

    public function __construct(StreamInterface $body, array $headers = [], string $version = '1.1')
    {
        $this->body = $body;
        $this->protocolVersion = $version;
        $this->setHeadersFast($headers);
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        if ($this->protocolVersion === $version) {
            return $this;
        }

        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $normalizedName = strtolower($name);

        if (!isset($this->headerNames[$normalizedName])) {
            return [];
        }

        return $this->headers[$this->headerNames[$normalizedName]] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $normalized = strtolower($name);
        $clone = clone $this;

        // Remove existing header if present
        if (isset($clone->headerNames[$normalized])) {
            unset($clone->headers[$clone->headerNames[$normalized]]);
        }

        $clone->headerNames[$normalized] = $name;
        $clone->headers[$name] = is_array($value) ? $value : [$value];

        return $clone;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $normalized = strtolower($name);
        $clone = clone $this;
        $valueArray = is_array($value) ? $value : [$value];

        if (isset($clone->headerNames[$normalized])) {
            $headerName = $clone->headerNames[$normalized];
            $clone->headers[$headerName] = array_merge($clone->headers[$headerName], $valueArray);
        } else {
            $clone->headerNames[$normalized] = $name;
            $clone->headers[$name] = $valueArray;
        }

        return $clone;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $normalized = strtolower($name);

        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $clone = clone $this;
        $headerName = $clone->headerNames[$normalized];

        unset($clone->headers[$headerName], $clone->headerNames[$normalized]);

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        if ($body === $this->body) {
            return $this;
        }

        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /**
     * Fast header setup without extensive validation
     */
    protected function setHeadersFast(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $normalized = strtolower($name);
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = is_array($value) ? $value : [$value];
        }
    }
}
