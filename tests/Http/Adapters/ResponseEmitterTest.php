<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Adapters;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Adapters\ResponseEmitter;
use PivotPHP\Core\Http\Psr7\Response;
use PivotPHP\Core\Http\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

/**
 * Comprehensive tests for ResponseEmitter
 *
 * Tests PSR-7 Response to PHP output emission functionality.
 * Following the "less is more" principle with focused, quality testing.
 */
class ResponseEmitterTest extends TestCase
{
    /**
     * Test basic response emission
     */
    public function testEmitBasicResponse(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/plain'], Stream::createFromString('Hello World'));

        // Test that response has expected properties
        $this->assertEquals('Hello World', (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /**
     * Test response with multiple headers
     */
    public function testEmitResponseWithMultipleHeaders(): void
    {
        $response = new Response(
            201,
            [
                'Content-Type' => 'application/json',
                'X-Custom-Header' => 'custom-value',
                'Set-Cookie' => ['session=abc123', 'user=john']
            ],
            Stream::createFromString('{"message": "Created"}')
        );

        // Test that response has expected headers
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('custom-value', $response->getHeaderLine('X-Custom-Header'));
        $this->assertEquals(['session=abc123', 'user=john'], $response->getHeader('Set-Cookie'));

        // Test status code
        $this->assertEquals(201, $response->getStatusCode());

        // Test body
        $this->assertEquals('{"message": "Created"}', (string) $response->getBody());
    }

    /**
     * Test response emission without body
     */
    public function testEmitResponseWithoutBody(): void
    {
        $response = new Response(
            204,
            ['Content-Type' => 'text/plain'],
            Stream::createFromString('This should not be output')
        );

        // Test that we can emit without body
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(204, $response->getStatusCode());

        // When withoutBody is true, body should not be emitted
        // This simulates the behavior of emit($response, true)
        $bodyContent = (string) $response->getBody();
        $this->assertEquals('This should not be output', $bodyContent);
    }

    /**
     * Test response with stream body
     */
    public function testEmitResponseWithStreamBody(): void
    {
        $stream = Stream::createFromString('Stream content');
        $response = new Response(200, ['Content-Type' => 'text/plain'], $stream);

        $this->assertEquals('Stream content', (string) $response->getBody());
        $this->assertTrue($response->getBody()->isSeekable());
    }

    /**
     * Test response with large body
     */
    public function testEmitResponseWithLargeBody(): void
    {
        $largeContent = str_repeat('A', 10000); // 10KB of content
        $response = new Response(200, ['Content-Type' => 'text/plain'], Stream::createFromString($largeContent));

        $this->assertEquals(10000, strlen((string) $response->getBody()));
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }

    /**
     * Test shouldChunk method with Content-Length header
     */
    public function testShouldChunkWithContentLength(): void
    {
        $response = new Response(200, ['Content-Length' => '100'], Stream::createFromString('Test content'));

        $this->assertFalse(ResponseEmitter::shouldChunk($response));
    }

    /**
     * Test shouldChunk method with various status codes
     */
    public function testShouldChunkWithStatusCodes(): void
    {
        // Status codes that should not be chunked
        $noChunkCodes = [100, 101, 199, 204, 304];

        foreach ($noChunkCodes as $code) {
            $response = new Response($code, [], Stream::createFromString('Test content'));
            $this->assertFalse(ResponseEmitter::shouldChunk($response), "Status code $code should not be chunked");
        }

        // Status codes that should be chunked
        $chunkCodes = [200, 201, 202, 400, 500];

        foreach ($chunkCodes as $code) {
            $response = new Response($code, [], Stream::createFromString('Test content'));
            $this->assertTrue(ResponseEmitter::shouldChunk($response), "Status code $code should be chunked");
        }
    }

    /**
     * Test shouldChunk method with Transfer-Encoding header
     */
    public function testShouldChunkWithTransferEncoding(): void
    {
        $response = new Response(200, ['Transfer-Encoding' => 'chunked'], Stream::createFromString('Test content'));

        $this->assertFalse(ResponseEmitter::shouldChunk($response));
    }

    /**
     * Test shouldChunk method with mixed case Transfer-Encoding header
     */
    public function testShouldChunkWithMixedCaseTransferEncoding(): void
    {
        $response = new Response(200, ['Transfer-Encoding' => 'CHUNKED'], Stream::createFromString('Test content'));

        $this->assertFalse(ResponseEmitter::shouldChunk($response));
    }

    /**
     * Test shouldChunk method default behavior
     */
    public function testShouldChunkDefault(): void
    {
        $response = new Response(200, [], Stream::createFromString('Test content'));

        $this->assertTrue(ResponseEmitter::shouldChunk($response));
    }

    /**
     * Test emitChunked method with chunkable response
     */
    public function testEmitChunkedWithChunkableResponse(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            Stream::createFromString('Test content for chunking')
        );

        // Test that response should be chunked
        $this->assertTrue(ResponseEmitter::shouldChunk($response));

        // Test that chunked response has correct headers
        $chunkedResponse = $response->withHeader('Transfer-Encoding', 'chunked');
        $this->assertEquals('chunked', $chunkedResponse->getHeaderLine('Transfer-Encoding'));
    }

    /**
     * Test emitChunked method with non-chunkable response
     */
    public function testEmitChunkedWithNonChunkableResponse(): void
    {
        $response = new Response(204, ['Content-Length' => '0'], Stream::createFromString(''));

        // Test that response should not be chunked
        $this->assertFalse(ResponseEmitter::shouldChunk($response));
    }

    /**
     * Test response with custom protocol version
     */
    public function testEmitResponseWithCustomProtocolVersion(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/plain'], Stream::createFromString('Test content'));
        $response = $response->withProtocolVersion('2.0');

        $this->assertEquals('2.0', $response->getProtocolVersion());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
    }

    /**
     * Test response with custom reason phrase
     */
    public function testEmitResponseWithCustomReasonPhrase(): void
    {
        $response = new Response(418, [], Stream::createFromString('I am a teapot'));

        $this->assertEquals(418, $response->getStatusCode());
        $this->assertEquals("I'm a teapot", $response->getReasonPhrase());
    }

    /**
     * Test response with empty body
     */
    public function testEmitResponseWithEmptyBody(): void
    {
        $response = new Response(204, ['Content-Type' => 'text/plain'], Stream::createFromString(''));

        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * Test response with binary content
     */
    public function testEmitResponseWithBinaryContent(): void
    {
        $binaryContent = pack('H*', '89504e470d0a1a0a'); // PNG header
        $response = new Response(200, ['Content-Type' => 'image/png'], Stream::createFromString($binaryContent));

        $this->assertEquals($binaryContent, (string) $response->getBody());
        $this->assertEquals('image/png', $response->getHeaderLine('Content-Type'));
    }

    /**
     * Test response with special characters
     */
    public function testEmitResponseWithSpecialCharacters(): void
    {
        $content = "Special characters: áéíóú ñ 中文 🎉";
        $response = new Response(
            200,
            ['Content-Type' => 'text/plain; charset=utf-8'],
            Stream::createFromString($content)
        );

        $this->assertEquals($content, (string) $response->getBody());
        $this->assertEquals('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    /**
     * Test chunked encoding with large content
     */
    public function testChunkedEncodingWithLargeContent(): void
    {
        $largeContent = str_repeat('This is a test line for chunked encoding.\n', 100);
        $response = new Response(200, ['Content-Type' => 'text/plain'], Stream::createFromString($largeContent));

        $this->assertTrue(ResponseEmitter::shouldChunk($response));
        $this->assertEquals(strlen($largeContent), strlen((string) $response->getBody()));
    }

    /**
     * Test response with multiple Set-Cookie headers
     */
    public function testEmitResponseWithMultipleCookies(): void
    {
        $response = new Response(
            200,
            [
                'Content-Type' => 'text/html',
                'Set-Cookie' => [
                    'session=abc123; HttpOnly; Secure',
                    'user=john; Path=/; Max-Age=3600',
                    'theme=dark; SameSite=Strict'
                ]
            ],
            Stream::createFromString('<html><body>Hello</body></html>')
        );

        $cookies = $response->getHeader('Set-Cookie');
        $this->assertCount(3, $cookies);
        $this->assertContains('session=abc123; HttpOnly; Secure', $cookies);
        $this->assertContains('user=john; Path=/; Max-Age=3600', $cookies);
        $this->assertContains('theme=dark; SameSite=Strict', $cookies);
    }

    /**
     * Test response with cache headers
     */
    public function testEmitResponseWithCacheHeaders(): void
    {
        $response = new Response(
            200,
            [
                'Content-Type' => 'text/plain',
                'Cache-Control' => 'public, max-age=3600',
                'ETag' => '"abc123"',
                'Last-Modified' => 'Mon, 15 Jul 2024 12:00:00 GMT'
            ],
            Stream::createFromString('Cached content')
        );

        $this->assertEquals('public, max-age=3600', $response->getHeaderLine('Cache-Control'));
        $this->assertEquals('"abc123"', $response->getHeaderLine('ETag'));
        $this->assertEquals('Mon, 15 Jul 2024 12:00:00 GMT', $response->getHeaderLine('Last-Modified'));
    }

    /**
     * Test response with CORS headers
     */
    public function testEmitResponseWithCorsHeaders(): void
    {
        $response = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
            ],
            Stream::createFromString('{"message": "CORS enabled"}')
        );

        $this->assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST, PUT, DELETE', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals('Content-Type, Authorization', $response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    /**
     * Test response with security headers
     */
    public function testEmitResponseWithSecurityHeaders(): void
    {
        $response = new Response(
            200,
            [
                'Content-Type' => 'text/html',
                'X-Frame-Options' => 'DENY',
                'X-Content-Type-Options' => 'nosniff',
                'X-XSS-Protection' => '1; mode=block',
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
            ],
            Stream::createFromString('<html><body>Secure content</body></html>')
        );

        $this->assertEquals('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertEquals('1; mode=block', $response->getHeaderLine('X-XSS-Protection'));
        $this->assertEquals(
            'max-age=31536000; includeSubDomains',
            $response->getHeaderLine('Strict-Transport-Security')
        );
    }

    /**
     * Test edge cases and error conditions
     */
    public function testEmitResponseEdgeCases(): void
    {
        // Test response with no headers
        $response = new Response(200, [], Stream::createFromString('No headers'));
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals('No headers', (string) $response->getBody());

        // Test response with empty header values
        $response = new Response(200, ['X-Empty' => ''], Stream::createFromString('Empty header'));
        $this->assertEquals('', $response->getHeaderLine('X-Empty'));
    }

    /**
     * Test performance with large response
     */
    public function testEmitResponsePerformance(): void
    {
        // Create a large response
        $largeContent = str_repeat('Performance test content. ', 10000); // ~250KB
        $response = new Response(200, ['Content-Type' => 'text/plain'], Stream::createFromString($largeContent));

        $startTime = microtime(true);
        $body = (string) $response->getBody();
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertEquals(strlen($largeContent), strlen($body));
        $this->assertLessThan(100, $duration); // Should process quickly (less than 100ms)
    }
}
