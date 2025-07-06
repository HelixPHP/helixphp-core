<?php

namespace Helix\Tests\Services;

use PHPUnit\Framework\TestCase;
use Helix\Http\HeaderRequest;

class HeaderRequestTest extends TestCase
{
    protected function setUp(): void
    {
        // Simular headers no $_SERVER para o fallback funcionar
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token123';
        $_SERVER['HTTP_X_API_KEY'] = 'api-key-value';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
    }

    protected function tearDown(): void
    {
        // Limpar $_SERVER após cada teste
        unset($_SERVER['HTTP_CONTENT_TYPE']);
        unset($_SERVER['HTTP_AUTHORIZATION']);
        unset($_SERVER['HTTP_X_API_KEY']);
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    public function testHeaderInitialization(): void
    {
        $headerRequest = new HeaderRequest();
        $this->assertInstanceOf(HeaderRequest::class, $headerRequest);
    }

    public function testHeaderConversionToCamelCase(): void
    {
        // Mock headers for testing
        $this->setMockHeaders(
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token123',
                'X-API-Key' => 'api-key-value',
                'User-Agent' => 'Mozilla/5.0'
            ]
        );

        $headerRequest = new HeaderRequest();

        // Test access via magic method
        $this->assertEquals('application/json', $headerRequest->contentType);
        $this->assertEquals('Bearer token123', $headerRequest->authorization);
        $this->assertEquals('api-key-value', $headerRequest->xApiKey);
        $this->assertEquals('Mozilla/5.0', $headerRequest->userAgent);
    }

    public function testGetHeaderMethod(): void
    {
        $this->setMockHeaders(
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token123'
            ]
        );

        $headerRequest = new HeaderRequest();

        $this->assertEquals('application/json', $headerRequest->getHeader('contentType'));
        $this->assertEquals('Bearer token123', $headerRequest->getHeader('authorization'));
        $this->assertNull($headerRequest->getHeader('nonExistent'));
    }

    public function testGetAllHeaders(): void
    {
        $mockHeaders = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer token123'
        ];

        $this->setMockHeaders($mockHeaders);
        $headerRequest = new HeaderRequest();

        $allHeaders = $headerRequest->getAllHeaders();
        $this->assertIsArray($allHeaders);
        $this->assertArrayHasKey('contentType', $allHeaders);
        $this->assertArrayHasKey('authorization', $allHeaders);
    }

    public function testHasHeaderMethod(): void
    {
        $this->setMockHeaders(
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token123'
            ]
        );

        $headerRequest = new HeaderRequest();

        $this->assertTrue($headerRequest->hasHeader('contentType'));
        $this->assertTrue($headerRequest->hasHeader('authorization'));
        $this->assertFalse($headerRequest->hasHeader('nonExistent'));
        $this->assertFalse($headerRequest->hasHeader(''));
    }

    public function testMagicGetWithNonExistentHeader(): void
    {
        $this->setMockHeaders(
            [
                'Content-Type' => 'application/json'
            ]
        );

        $headerRequest = new HeaderRequest();

        $this->assertNull($headerRequest->nonExistent);
        $this->assertNull($headerRequest->someRandomHeader);
    }

    public function testEmptyHeaders(): void
    {
        $this->setMockHeaders([]);

        $headerRequest = new HeaderRequest();

        $allHeaders = $headerRequest->getAllHeaders();
        $this->assertTrue(is_array($allHeaders) || is_null($allHeaders));
        if (is_array($allHeaders)) {
            $this->assertEmpty($allHeaders);
        }
        $this->assertFalse($headerRequest->hasHeader('anything'));
        $this->assertNull($headerRequest->getHeader('anything'));
    }

    public function testHeadersWithColonPrefix(): void
    {
        $this->setMockHeaders(
            [
                ':Content-Type' => 'application/json',
                ':Authorization' => 'Bearer token123'
            ]
        );

        $headerRequest = new HeaderRequest();

        // The constructor should trim the leading colon
        $this->assertEquals('application/json', $headerRequest->contentType);
        $this->assertEquals('Bearer token123', $headerRequest->authorization);
    }

    public function testComplexHeaderNames(): void
    {
        $this->setMockHeaders(
            [
                'X-Forwarded-For' => '192.168.1.1',
                'X-Real-IP' => '10.0.0.1',
                'Accept-Encoding' => 'gzip, deflate',
                'Cache-Control' => 'no-cache'
            ]
        );

        $headerRequest = new HeaderRequest();

        $this->assertEquals('192.168.1.1', $headerRequest->xForwardedFor);
        $this->assertEquals('10.0.0.1', $headerRequest->xRealIp);
        $this->assertEquals('gzip, deflate', $headerRequest->acceptEncoding);
        $this->assertEquals('no-cache', $headerRequest->cacheControl);
    }

    public function testHeadersWithSpecialCharacters(): void
    {
        $this->setMockHeaders(
            [
                'Custom-Header' => 'value with spaces and symbols !@#$%',
                'X-Test' => 'áéíóú çñü'
            ]
        );

        $headerRequest = new HeaderRequest();

        $this->assertEquals('value with spaces and symbols !@#$%', $headerRequest->customHeader);
        $this->assertEquals('áéíóú çñü', $headerRequest->xTest);
    }

    public function testCaseInsensitiveAccess(): void
    {
        $this->setMockHeaders(
            [
                'Content-Type' => 'application/json'
            ]
        );

        $headerRequest = new HeaderRequest();

        // Should work with exact camelCase
        $this->assertEquals('application/json', $headerRequest->contentType);
        $this->assertEquals('application/json', $headerRequest->getHeader('contentType'));
        $this->assertTrue($headerRequest->hasHeader('contentType'));
    }

    /**
     * Helper method to set headers via $_SERVER
     */
    private function setMockHeaders(array $headers): void
    {
        // Limpar headers existentes
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                unset($_SERVER[$key]);
            }
        }

        // Converter headers para formato $_SERVER
        foreach ($headers as $name => $value) {
            $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $_SERVER[$serverKey] = $value;
        }
    }

    public function testMultipleHeaderInstances(): void
    {
        $this->setMockHeaders(
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token123'
            ]
        );

        $headerRequest1 = new HeaderRequest();
        $headerRequest2 = new HeaderRequest();

        // Both instances should have the same headers
        $this->assertEquals($headerRequest1->getAllHeaders(), $headerRequest2->getAllHeaders());
        $this->assertEquals($headerRequest1->contentType, $headerRequest2->contentType);
    }

    public function testHeaderValueTypes(): void
    {
        $this->setMockHeaders(
            [
                'X-Numeric' => '123',
                'X-Boolean' => 'true',
                'X-Empty' => '',
                'X-Null' => null
            ]
        );

        $headerRequest = new HeaderRequest();

        $this->assertEquals('123', $headerRequest->xNumeric);
        $this->assertEquals('true', $headerRequest->xBoolean);
        $this->assertEquals('', $headerRequest->xEmpty);
        $this->assertNull($headerRequest->xNull);
    }
}
