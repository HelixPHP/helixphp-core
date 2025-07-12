<?php

declare(strict_types=1);

namespace PivotPHP\Core\Routing;

/**
 * Mock Response para capturar responses estáticas
 */
class MockResponse
{
    private string $content = '';

    /** @var array<string, string> */
    private array $headers = [];

    private int $statusCode = 200;

    public function send(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param array<mixed> $data
     */
    public function json(array $data): self
    {
        $json = json_encode($data);
        $this->content = $json !== false ? $json : '';
        $this->headers['Content-Type'] = 'application/json';
        return $this;
    }

    public function write(string $content): self
    {
        $this->content .= $content;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param array<mixed> $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return $this;
    }
}
