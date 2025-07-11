<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

/**
 * Test response wrapper
 */
class TestResponse
{
    private int $statusCode;
    private array $headers;
    private string $body;

    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeader(string $name): ?string
    {
        $header = $this->headers[$name] ?? null;
        if (is_array($header)) {
            return $header[0] ?? null;
        }
        return $header;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getJsonData(): array
    {
        return json_decode($this->body, true) ?? [];
    }
}
