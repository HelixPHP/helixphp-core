<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Psr7;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * Comprehensive tests for Stream class
 *
 * Tests PSR-7 StreamInterface implementation including read/write operations,
 * seek functionality, resource management, and stream creation methods.
 * Following the "less is more" principle with focused, quality testing.
 */
class StreamTest extends TestCase
{
    /**
     * Test stream creation from string
     */
    public function testStreamCreationFromString(): void
    {
        $content = 'Hello, World!';
        $stream = Stream::createFromString($content);

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals($content, (string) $stream);
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
    }

    /**
     * Test stream creation from resource
     */
    public function testStreamCreationFromResource(): void
    {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Test content');
        rewind($resource);

        $stream = new Stream($resource);

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals('Test content', (string) $stream);
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
    }

    /**
     * Test stream creation from file
     */
    public function testStreamCreationFromFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'stream_test');
        file_put_contents($tempFile, 'File content');

        try {
            $stream = Stream::createFromFile($tempFile);

            $this->assertInstanceOf(StreamInterface::class, $stream);
            $this->assertEquals('File content', (string) $stream);
            $this->assertTrue($stream->isReadable());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test stream reading operations
     */
    public function testStreamReadingOperations(): void
    {
        $content = 'Hello, World!';
        $stream = Stream::createFromString($content);

        $this->assertEquals('Hello', $stream->read(5));
        $this->assertEquals(', World!', $stream->read(100));
        $this->assertEquals('', $stream->read(5)); // EOF
    }

    /**
     * Test stream writing operations
     */
    public function testStreamWritingOperations(): void
    {
        $stream = Stream::createFromString('');

        $bytesWritten = $stream->write('Hello');
        $this->assertEquals(5, $bytesWritten);

        $bytesWritten = $stream->write(', World!');
        $this->assertEquals(8, $bytesWritten);

        $stream->rewind();
        $this->assertEquals('Hello, World!', (string) $stream);
    }

    /**
     * Test stream seeking operations
     */
    public function testStreamSeekingOperations(): void
    {
        $stream = Stream::createFromString('Hello, World!');

        $stream->seek(7);
        $this->assertEquals('World!', $stream->read(10));

        $stream->seek(0);
        $this->assertEquals('Hello', $stream->read(5));

        $stream->rewind();
        $this->assertEquals('Hello, World!', (string) $stream);
    }

    /**
     * Test stream size calculation
     */
    public function testStreamSizeCalculation(): void
    {
        $content = 'Hello, World!';
        $stream = Stream::createFromString($content);

        $this->assertEquals(13, $stream->getSize());
        $this->assertEquals(strlen($content), $stream->getSize());
    }

    /**
     * Test stream position tracking
     */
    public function testStreamPositionTracking(): void
    {
        $stream = Stream::createFromString('Hello, World!');

        $this->assertEquals(0, $stream->tell());

        $stream->read(5);
        $this->assertEquals(5, $stream->tell());

        $stream->seek(7);
        $this->assertEquals(7, $stream->tell());

        $stream->rewind();
        $this->assertEquals(0, $stream->tell());
    }

    /**
     * Test stream EOF detection
     */
    public function testStreamEofDetection(): void
    {
        $stream = Stream::createFromString('Hello');

        $this->assertFalse($stream->eof());

        $stream->read(5);
        $stream->read(1); // Try to read past end
        $this->assertTrue($stream->eof());

        $stream->rewind();
        $this->assertFalse($stream->eof());
    }

    /**
     * Test stream contents retrieval
     */
    public function testStreamContentsRetrieval(): void
    {
        $stream = Stream::createFromString('Hello, World!');

        $stream->seek(7);
        $this->assertEquals('World!', $stream->getContents());

        $stream->rewind();
        $this->assertEquals('Hello, World!', $stream->getContents());
    }

    /**
     * Test stream truncation
     */
    public function testStreamTruncation(): void
    {
        $stream = Stream::createFromString('Hello, World!');

        $stream->truncate(5);
        $stream->rewind();

        $this->assertEquals('Hello', (string) $stream);
        $this->assertEquals(5, $stream->getSize());
    }

    /**
     * Test stream metadata
     */
    public function testStreamMetadata(): void
    {
        $stream = Stream::createFromString('Hello, World!');

        $metadata = $stream->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('mode', $metadata);

        $mode = $stream->getMetadata('mode');
        $this->assertIsString($mode);
        $this->assertNotEmpty($mode);
    }

    /**
     * Test stream detachment
     */
    public function testStreamDetachment(): void
    {
        $stream = Stream::createFromString('Hello, World!');

        $resource = $stream->detach();
        $this->assertIsResource($resource);

        // Stream should be unusable after detachment
        $this->assertNull($stream->detach());
        $this->assertNull($stream->getSize());
        $this->assertEquals('', (string) $stream);
    }

    /**
     * Test stream closure
     */
    public function testStreamClosure(): void
    {
        $stream = Stream::createFromString('Hello, World!');

        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());

        $stream->close();

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * Test invalid stream resource
     */
    public function testInvalidStreamResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream must be a resource or string');

        new Stream(123);
    }

    /**
     * Test stream reusability
     */
    public function testStreamReusability(): void
    {
        $stream = Stream::createFromString('Hello, World!');

        $this->assertTrue($stream->isReusable());

        // After detachment, stream should not be reusable
        $stream->detach();
        $this->assertFalse($stream->isReusable());
    }

    /**
     * Test non-seekable stream
     */
    public function testNonSeekableStream(): void
    {
        // Use a pipe which is not seekable
        $pipes = [];
        $process = proc_open(
            'echo "test"',
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );

        if (is_resource($process)) {
            $stream = new Stream($pipes[1]);

            // If it's not seekable, trying to seek should throw exception
            if (!$stream->isSeekable()) {
                $this->expectException(\RuntimeException::class);
                $stream->seek(0);
            } else {
                // Skip test if stream is actually seekable
                $this->markTestSkipped('Stream is seekable on this system');
            }

            proc_close($process);
        } else {
            $this->markTestSkipped('Unable to create non-seekable stream');
        }
    }

    /**
     * Test non-writable stream
     */
    public function testNonWritableStream(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'readonly_test');
        file_put_contents($tempFile, 'Read only content');

        try {
            $resource = fopen($tempFile, 'r');
            $stream = new Stream($resource);

            $this->assertFalse($stream->isWritable());

            $this->expectException(\RuntimeException::class);
            $stream->write('test');
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test large stream operations
     */
    public function testLargeStreamOperations(): void
    {
        $largeContent = str_repeat('A', 100000);
        $stream = Stream::createFromString($largeContent);

        $this->assertEquals(100000, $stream->getSize());
        $this->assertEquals($largeContent, (string) $stream);

        $stream->seek(50000);
        $this->assertEquals(50000, $stream->tell());

        $chunk = $stream->read(1000);
        $this->assertEquals(1000, strlen($chunk));
        $this->assertEquals(str_repeat('A', 1000), $chunk);
    }

    /**
     * Test stream with binary data
     */
    public function testStreamWithBinaryData(): void
    {
        $binaryData = "\x00\x01\x02\x03\x04\x05";
        $stream = Stream::createFromString($binaryData);

        $this->assertEquals(6, $stream->getSize());
        $this->assertEquals($binaryData, (string) $stream);

        $stream->rewind();
        $this->assertEquals($binaryData, $stream->read(6));
    }

    /**
     * Test stream performance
     */
    public function testStreamPerformance(): void
    {
        $startTime = microtime(true);

        // Create many streams
        $streams = [];
        for ($i = 0; $i < 100; $i++) {
            $streams[] = Stream::createFromString("Content {$i}");
        }

        // Read from all streams
        foreach ($streams as $stream) {
            $content = (string) $stream;
            $this->assertNotEmpty($content);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(100, $duration); // Should be fast
    }

    /**
     * Test stream copy operations
     */
    public function testStreamCopyOperations(): void
    {
        $sourceStream = Stream::createFromString('Source content');
        $targetStream = Stream::createFromString('');

        // Copy content
        $sourceStream->rewind();
        while (!$sourceStream->eof()) {
            $chunk = $sourceStream->read(4096);
            if ($chunk !== '') {
                $targetStream->write($chunk);
            }
        }

        $targetStream->rewind();
        $this->assertEquals('Source content', (string) $targetStream);
    }

    /**
     * Test stream error handling
     */
    public function testStreamErrorHandling(): void
    {
        $stream = Stream::createFromString('Hello, World!');
        $stream->close();

        $this->expectException(\RuntimeException::class);
        $stream->read(5);
    }
}
