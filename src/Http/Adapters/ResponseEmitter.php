<?php

declare(strict_types=1);

namespace Express\Http\Adapters;

use Psr\Http\Message\ResponseInterface;

/**
 * Adapter to emit PSR-7 Response to PHP output
 */
class ResponseEmitter
{
    /**
     * Emit the response to the client
     */
    public static function emit(ResponseInterface $response, bool $withoutBody = false): void
    {
        // Check if headers have already been sent
        if (headers_sent()) {
            throw new \RuntimeException('Headers have already been sent');
        }

        // Set status line
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true);

        // Set headers
        foreach ($response->getHeaders() as $name => $values) {
            $first = true;
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $first);
                $first = false;
            }
        }

        // Output body if not excluded
        if (!$withoutBody) {
            $body = $response->getBody();

            if ($body->isSeekable()) {
                $body->rewind();
            }

            while (!$body->eof()) {
                echo $body->read(8192);
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }

    /**
     * Check if the response should be chunked
     */
    public static function shouldChunk(ResponseInterface $response): bool
    {
        // Don't chunk if Content-Length is set
        if ($response->hasHeader('Content-Length')) {
            return false;
        }

        // Don't chunk for certain status codes
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode === 204 || $statusCode === 304) {
            return false;
        }

        // Check if Transfer-Encoding chunked is already set
        $transferEncoding = $response->getHeaderLine('Transfer-Encoding');
        if (stripos($transferEncoding, 'chunked') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Emit response with chunked transfer encoding
     */
    public static function emitChunked(ResponseInterface $response): void
    {
        if (!self::shouldChunk($response)) {
            self::emit($response);
            return;
        }

        // Add Transfer-Encoding header
        $response = $response->withHeader('Transfer-Encoding', 'chunked');

        // Emit headers
        self::emit($response, true);

        // Emit body in chunks
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            $chunk = $body->read(8192);
            if ($chunk === '') {
                break;
            }

            echo dechex(strlen($chunk)) . "\r\n";
            echo $chunk . "\r\n";

            if (connection_status() !== CONNECTION_NORMAL) {
                break;
            }
        }

        // End with zero-length chunk
        echo "0\r\n\r\n";
    }
}
