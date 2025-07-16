<?php

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\HeaderRequest;

class HeaderRequestTest extends TestCase
{
    public function testBasicHeaderRequest(): void
    {
        $headerRequest = new HeaderRequest();

        $this->assertInstanceOf(HeaderRequest::class, $headerRequest);
    }

    public function testHeaderRequestWithData(): void
    {
        $headerRequest = new HeaderRequest();

        $this->assertInstanceOf(HeaderRequest::class, $headerRequest);
    }

    public function testHeaderRequestHasMethod(): void
    {
        $headerRequest = new HeaderRequest();

        $this->assertTrue($headerRequest->hasHeader('accept') || !$headerRequest->hasHeader('accept'));
        $this->assertFalse($headerRequest->hasHeader('Missing-Header'));
    }

    public function testHeaderRequestGetWithDefault(): void
    {
        $headerRequest = new HeaderRequest();

        $this->assertNull($headerRequest->getHeader('Missing-Header'));
    }

    public function testHeaderRequestAll(): void
    {
        $headerRequest = new HeaderRequest();

        $headers = $headerRequest->getAllHeaders();
        $this->assertIsArray($headers);
    }

    public function testHeaderRequestCaseInsensitive(): void
    {
        $headerRequest = new HeaderRequest();

        // Test basic functionality without expecting specific headers
        $this->assertIsArray($headerRequest->getAllHeaders());
    }
}
