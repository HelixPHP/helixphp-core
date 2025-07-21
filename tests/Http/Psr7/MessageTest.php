<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Psr7;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Psr7\Message;
use PivotPHP\Core\Http\Psr7\Stream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Comprehensive tests for Message class
 *
 * Tests PSR-7 Message implementation including header management,
 * protocol version handling, body operations, and header pooling.
 * Following the "less is more" principle with focused, quality testing.
 */
class MessageTest extends TestCase
{
    private Message $message;
    private StreamInterface $body;

    protected function setUp(): void
    {
        parent::setUp();
        $this->body = Stream::createFromString('Test body content');
        $this->message = new Message($this->body);
    }

    /**
     * Test basic message creation
     */
    public function testBasicMessageCreation(): void
    {
        $this->assertInstanceOf(MessageInterface::class, $this->message);
        $this->assertInstanceOf(Message::class, $this->message);
        $this->assertEquals('1.1', $this->message->getProtocolVersion());
        $this->assertEquals($this->body, $this->message->getBody());
    }

    /**
     * Test message creation with headers
     */
    public function testMessageCreationWithHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'custom-value'
        ];

        $message = new Message($this->body, $headers);

        $expectedHeaders = [
            'Content-Type' => ['application/json'],
            'X-Custom-Header' => ['custom-value']
        ];

        $this->assertEquals($expectedHeaders, $message->getHeaders());
        $this->assertEquals(['application/json'], $message->getHeader('Content-Type'));
        $this->assertEquals('custom-value', $message->getHeaderLine('X-Custom-Header'));
    }

    /**
     * Test message creation with custom protocol version
     */
    public function testMessageCreationWithCustomProtocolVersion(): void
    {
        $message = new Message($this->body, [], '2.0');

        $this->assertEquals('2.0', $message->getProtocolVersion());
    }

    /**
     * Test protocol version handling
     */
    public function testProtocolVersionHandling(): void
    {
        $this->assertEquals('1.1', $this->message->getProtocolVersion());

        // Test with protocol version change
        $newMessage = $this->message->withProtocolVersion('2.0');

        $this->assertEquals('1.1', $this->message->getProtocolVersion());
        $this->assertEquals('2.0', $newMessage->getProtocolVersion());
        $this->assertNotSame($this->message, $newMessage);
    }

    /**
     * Test protocol version immutability
     */
    public function testProtocolVersionImmutability(): void
    {
        $sameMessage = $this->message->withProtocolVersion('1.1');

        $this->assertSame($this->message, $sameMessage);
    }

    /**
     * Test header case insensitivity
     */
    public function testHeaderCaseInsensitivity(): void
    {
        $message = $this->message->withHeader('Content-Type', 'application/json');

        $this->assertTrue($message->hasHeader('content-type'));
        $this->assertTrue($message->hasHeader('Content-Type'));
        $this->assertTrue($message->hasHeader('CONTENT-TYPE'));
        $this->assertEquals(['application/json'], $message->getHeader('content-type'));
        $this->assertEquals(['application/json'], $message->getHeader('Content-Type'));
        $this->assertEquals(['application/json'], $message->getHeader('CONTENT-TYPE'));
    }

    /**
     * Test header retrieval
     */
    public function testHeaderRetrieval(): void
    {
        $message = $this->message
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Custom-Header', ['value1', 'value2']);

        $this->assertEquals(['application/json'], $message->getHeader('Content-Type'));
        $this->assertEquals(['value1', 'value2'], $message->getHeader('X-Custom-Header'));
        $this->assertEquals('value1, value2', $message->getHeaderLine('X-Custom-Header'));
        $this->assertEquals([], $message->getHeader('Non-Existent'));
        $this->assertEquals('', $message->getHeaderLine('Non-Existent'));
    }

    /**
     * Test header presence check
     */
    public function testHeaderPresenceCheck(): void
    {
        $message = $this->message->withHeader('Content-Type', 'application/json');

        $this->assertTrue($message->hasHeader('Content-Type'));
        $this->assertTrue($message->hasHeader('content-type'));
        $this->assertFalse($message->hasHeader('Authorization'));
    }

    /**
     * Test header replacement
     */
    public function testHeaderReplacement(): void
    {
        $message = $this->message
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Content-Type', 'text/plain');

        $this->assertEquals(['text/plain'], $message->getHeader('Content-Type'));
        $this->assertEquals('text/plain', $message->getHeaderLine('Content-Type'));
    }

    /**
     * Test header addition
     */
    public function testHeaderAddition(): void
    {
        $message = $this->message
            ->withHeader('Accept', 'application/json')
            ->withAddedHeader('Accept', 'application/xml');

        $this->assertEquals(['application/json', 'application/xml'], $message->getHeader('Accept'));
        $this->assertEquals('application/json, application/xml', $message->getHeaderLine('Accept'));
    }

    /**
     * Test header addition to new header
     */
    public function testHeaderAdditionToNewHeader(): void
    {
        $message = $this->message->withAddedHeader('Content-Type', 'application/json');

        $this->assertEquals(['application/json'], $message->getHeader('Content-Type'));
        $this->assertEquals('application/json', $message->getHeaderLine('Content-Type'));
    }

    /**
     * Test header removal
     */
    public function testHeaderRemoval(): void
    {
        $message = $this->message
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer token')
            ->withoutHeader('Content-Type');

        $this->assertFalse($message->hasHeader('Content-Type'));
        $this->assertTrue($message->hasHeader('Authorization'));
        $this->assertEquals([], $message->getHeader('Content-Type'));
        $this->assertEquals(['Bearer token'], $message->getHeader('Authorization'));
    }

    /**
     * Test header removal immutability
     */
    public function testHeaderRemovalImmutability(): void
    {
        $sameMessage = $this->message->withoutHeader('Non-Existent');

        $this->assertSame($this->message, $sameMessage);
    }

    /**
     * Test multiple header operations
     */
    public function testMultipleHeaderOperations(): void
    {
        $message = $this->message
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withAddedHeader('Accept', 'application/xml')
            ->withHeader('Authorization', 'Bearer token')
            ->withoutHeader('Content-Type');

        $expected = [
            'Accept' => ['application/json', 'application/xml'],
            'Authorization' => ['Bearer token']
        ];

        $this->assertEquals($expected, $message->getHeaders());
    }

    /**
     * Test header values as array
     */
    public function testHeaderValuesAsArray(): void
    {
        $message = $this->message->withHeader('Accept', ['application/json', 'application/xml']);

        $this->assertEquals(['application/json', 'application/xml'], $message->getHeader('Accept'));
        $this->assertEquals('application/json, application/xml', $message->getHeaderLine('Accept'));
    }

    /**
     * Test header values as string
     */
    public function testHeaderValuesAsString(): void
    {
        $message = $this->message->withHeader('Content-Type', 'application/json');

        $this->assertEquals(['application/json'], $message->getHeader('Content-Type'));
        $this->assertEquals('application/json', $message->getHeaderLine('Content-Type'));
    }

    /**
     * Test body handling
     */
    public function testBodyHandling(): void
    {
        $this->assertEquals($this->body, $this->message->getBody());
        $this->assertEquals('Test body content', (string) $this->message->getBody());
    }

    /**
     * Test body replacement
     */
    public function testBodyReplacement(): void
    {
        $newBody = Stream::createFromString('New body content');
        $message = $this->message->withBody($newBody);

        $this->assertEquals($this->body, $this->message->getBody());
        $this->assertEquals($newBody, $message->getBody());
        $this->assertNotSame($this->message, $message);
    }

    /**
     * Test body replacement immutability
     */
    public function testBodyReplacementImmutability(): void
    {
        $sameMessage = $this->message->withBody($this->body);

        $this->assertSame($this->message, $sameMessage);
    }

    /**
     * Test message immutability
     */
    public function testMessageImmutability(): void
    {
        $original = $this->message;

        $withHeader = $original->withHeader('Content-Type', 'application/json');
        $withProtocol = $original->withProtocolVersion('2.0');
        $withBody = $original->withBody(Stream::createFromString('New body'));

        $this->assertNotSame($original, $withHeader);
        $this->assertNotSame($original, $withProtocol);
        $this->assertNotSame($original, $withBody);

        // Original should remain unchanged
        $this->assertEquals('1.1', $original->getProtocolVersion());
        $this->assertEquals([], $original->getHeaders());
        $this->assertEquals($this->body, $original->getBody());
    }

    /**
     * Test strict header validation
     */
    public function testStrictHeaderValidation(): void
    {
        $message = $this->message->withHeaderStrict('Content-Type', 'application/json');

        $this->assertEquals(['application/json'], $message->getHeader('Content-Type'));
        $this->assertEquals('application/json', $message->getHeaderLine('Content-Type'));
    }

    /**
     * Test strict header addition
     */
    public function testStrictHeaderAddition(): void
    {
        $message = $this->message
            ->withHeaderStrict('Accept', 'application/json')
            ->withAddedHeaderStrict('Accept', 'application/xml');

        $this->assertEquals(['application/json', 'application/xml'], $message->getHeader('Accept'));
        $this->assertEquals('application/json, application/xml', $message->getHeaderLine('Accept'));
    }

    /**
     * Test header with empty value
     */
    public function testHeaderWithEmptyValue(): void
    {
        $message = $this->message->withHeader('X-Empty', '');

        $this->assertTrue($message->hasHeader('X-Empty'));
        $this->assertEquals([''], $message->getHeader('X-Empty'));
        $this->assertEquals('', $message->getHeaderLine('X-Empty'));
    }

    /**
     * Test header with numeric value
     */
    public function testHeaderWithNumericValue(): void
    {
        $message = $this->message->withHeader('Content-Length', 123);

        $this->assertEquals(['123'], $message->getHeader('Content-Length'));
        $this->assertEquals('123', $message->getHeaderLine('Content-Length'));
    }

    /**
     * Test header with boolean value
     */
    public function testHeaderWithBooleanValue(): void
    {
        $message = $this->message->withHeader('X-Debug', true);

        $this->assertEquals(['1'], $message->getHeader('X-Debug'));
        $this->assertEquals('1', $message->getHeaderLine('X-Debug'));
    }

    /**
     * Test header normalization
     */
    public function testHeaderNormalization(): void
    {
        $message = $this->message
            ->withHeader('content-type', 'application/json')
            ->withHeader('Content-Type', 'text/plain');

        // Should have only one Content-Type header (replaced)
        $this->assertEquals(['text/plain'], $message->getHeader('Content-Type'));
        $this->assertEquals('text/plain', $message->getHeaderLine('content-type'));
    }

    /**
     * Test complex header operations
     */
    public function testComplexHeaderOperations(): void
    {
        $message = $this->message
            ->withHeader('Accept', 'application/json')
            ->withAddedHeader('Accept', 'application/xml')
            ->withAddedHeader('Accept', 'text/plain')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer token123')
            ->withoutHeader('Accept');

        $expected = [
            'Content-Type' => ['application/json'],
            'Authorization' => ['Bearer token123']
        ];

        $this->assertEquals($expected, $message->getHeaders());
        $this->assertFalse($message->hasHeader('Accept'));
    }

    /**
     * Test header preservation during operations
     */
    public function testHeaderPreservationDuringOperations(): void
    {
        $message = $this->message
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer token');

        $newMessage = $message->withProtocolVersion('2.0');

        // Headers should be preserved
        $this->assertEquals($message->getHeaders(), $newMessage->getHeaders());
        $this->assertEquals('application/json', $newMessage->getHeaderLine('Content-Type'));
        $this->assertEquals('Bearer token', $newMessage->getHeaderLine('Authorization'));
    }

    /**
     * Test header preservation during body operations
     */
    public function testHeaderPreservationDuringBodyOperations(): void
    {
        $message = $this->message
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer token');

        $newBody = Stream::createFromString('New content');
        $newMessage = $message->withBody($newBody);

        // Headers should be preserved
        $this->assertEquals($message->getHeaders(), $newMessage->getHeaders());
        $this->assertEquals('application/json', $newMessage->getHeaderLine('Content-Type'));
        $this->assertEquals('Bearer token', $newMessage->getHeaderLine('Authorization'));
    }

    /**
     * Test header edge cases
     */
    public function testHeaderEdgeCases(): void
    {
        $message = $this->message
            ->withHeader('X-Test', ['value1', 'value2', 'value3'])
            ->withAddedHeader('X-Test', 'value4');

        $this->assertEquals(['value1', 'value2', 'value3', 'value4'], $message->getHeader('X-Test'));
        $this->assertEquals('value1, value2, value3, value4', $message->getHeaderLine('X-Test'));
    }

    /**
     * Test message cloning behavior
     */
    public function testMessageCloningBehavior(): void
    {
        $message = $this->message->withHeader('Content-Type', 'application/json');

        $clonedMessage = $message->withHeader('Authorization', 'Bearer token');

        // Original message should not have Authorization header
        $this->assertFalse($message->hasHeader('Authorization'));
        $this->assertTrue($clonedMessage->hasHeader('Authorization'));

        // Both should have Content-Type
        $this->assertTrue($message->hasHeader('Content-Type'));
        $this->assertTrue($clonedMessage->hasHeader('Content-Type'));
    }

    /**
     * Test getHeaders returns proper structure
     */
    public function testGetHeadersReturnsProperStructure(): void
    {
        $message = $this->message
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', ['application/json', 'application/xml'])
            ->withHeader('Authorization', 'Bearer token');

        $headers = $message->getHeaders();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Authorization', $headers);

        // Each header value should be an array
        $this->assertIsArray($headers['Content-Type']);
        $this->assertIsArray($headers['Accept']);
        $this->assertIsArray($headers['Authorization']);

        $this->assertEquals(['application/json'], $headers['Content-Type']);
        $this->assertEquals(['application/json', 'application/xml'], $headers['Accept']);
        $this->assertEquals(['Bearer token'], $headers['Authorization']);
    }

    /**
     * Test performance with many headers
     */
    public function testPerformanceWithManyHeaders(): void
    {
        $message = $this->message;

        $startTime = microtime(true);

        // Add many headers
        for ($i = 0; $i < 100; $i++) {
            $message = $message->withHeader("X-Header-{$i}", "value{$i}");
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertLessThan(100, $duration); // Should be fast (less than 100ms)
        $this->assertCount(100, $message->getHeaders());
    }

    /**
     * Test header line with single value
     */
    public function testHeaderLineWithSingleValue(): void
    {
        $message = $this->message->withHeader('Content-Type', 'application/json');

        $this->assertEquals('application/json', $message->getHeaderLine('Content-Type'));
    }

    /**
     * Test header line with multiple values
     */
    public function testHeaderLineWithMultipleValues(): void
    {
        $message = $this->message->withHeader('Accept', ['application/json', 'application/xml', 'text/plain']);

        $this->assertEquals('application/json, application/xml, text/plain', $message->getHeaderLine('Accept'));
    }

    /**
     * Test header line with empty header
     */
    public function testHeaderLineWithEmptyHeader(): void
    {
        $this->assertEquals('', $this->message->getHeaderLine('Non-Existent'));
    }
}
