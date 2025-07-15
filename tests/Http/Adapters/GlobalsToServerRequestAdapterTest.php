<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Adapters;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Adapters\GlobalsToServerRequestAdapter;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Uri;
use PivotPHP\Core\Http\Psr7\Stream;
use PivotPHP\Core\Http\Psr7\UploadedFile;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Comprehensive tests for GlobalsToServerRequestAdapter
 *
 * Tests PHP globals to PSR-7 ServerRequest conversion functionality.
 * Following the "less is more" principle with focused, quality testing.
 */
class GlobalsToServerRequestAdapterTest extends TestCase
{
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;
    private array $originalCookie;
    private array $originalFiles;

    protected function setUp(): void
    {
        parent::setUp();

        // Backup original global variables
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalCookie = $_COOKIE;
        $this->originalFiles = $_FILES;
    }

    protected function tearDown(): void
    {
        // Restore original global variables
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_COOKIE = $this->originalCookie;
        $_FILES = $this->originalFiles;

        parent::tearDown();
    }

    /**
     * Test basic GET request creation from globals
     */
    public function testFromGlobalsBasicGetRequest(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/test',
            'SERVER_PROTOCOL' => '1.1'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('example.com', $request->getUri()->getHost());
        $this->assertEquals('/test', $request->getUri()->getPath());
        $this->assertEquals('1.1', $request->getProtocolVersion());
    }

    /**
     * Test POST request with parsed body
     */
    public function testFromGlobalsPostRequestWithBody(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/submit',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded'
        ];

        $body = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server, [], $body);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($body, $request->getParsedBody());
        $this->assertEquals('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
    }

    /**
     * Test request with query parameters
     */
    public function testFromGlobalsWithQueryParams(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/search?q=test&page=1',
            'QUERY_STRING' => 'q=test&page=1'
        ];

        $query = [
            'q' => 'test',
            'page' => '1'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server, $query);

        $this->assertEquals($query, $request->getQueryParams());
        $this->assertEquals('q=test&page=1', $request->getUri()->getQuery());
    }

    /**
     * Test request with cookies
     */
    public function testFromGlobalsWithCookies(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/'
        ];

        $cookies = [
            'session_id' => 'abc123',
            'user_pref' => 'dark_mode'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server, [], [], $cookies);

        $this->assertEquals($cookies, $request->getCookieParams());
    }

    /**
     * Test HTTPS request creation
     */
    public function testFromGlobalsHttpsRequest(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'secure.example.com',
            'REQUEST_URI' => '/secure',
            'HTTPS' => 'on',
            'SERVER_PORT' => '443'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertEquals('https', $request->getUri()->getScheme());
        $this->assertEquals('secure.example.com', $request->getUri()->getHost());
        // Standard HTTPS port (443) should not be explicitly set
        $this->assertNull($request->getUri()->getPort());
    }

    /**
     * Test request with custom port
     */
    public function testFromGlobalsWithCustomPort(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/test',
            'SERVER_PORT' => '8080'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertEquals(8080, $request->getUri()->getPort());
    }

    /**
     * Test request with various headers
     */
    public function testFromGlobalsWithHeaders(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/api',
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer token123',
            'HTTP_USER_AGENT' => 'TestClient/1.0',
            'HTTP_X_CUSTOM_HEADER' => 'custom-value',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => '100'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('Bearer token123', $request->getHeaderLine('Authorization'));
        $this->assertEquals('TestClient/1.0', $request->getHeaderLine('User-Agent'));
        $this->assertEquals('custom-value', $request->getHeaderLine('X-Custom-Header'));
        $this->assertEquals('100', $request->getHeaderLine('Content-Length'));
    }

    /**
     * Test request with uploaded files
     */
    public function testFromGlobalsWithUploadedFiles(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/upload'
        ];

        // Create temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, 'test content');

        $files = [
            'document' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $tempFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 12
            ]
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server, [], [], [], $files);

        $uploadedFiles = $request->getUploadedFiles();
        $this->assertArrayHasKey('document', $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['document']);
        $this->assertEquals('test.txt', $uploadedFiles['document']->getClientFilename());
        $this->assertEquals('text/plain', $uploadedFiles['document']->getClientMediaType());
        $this->assertEquals(12, $uploadedFiles['document']->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $uploadedFiles['document']->getError());

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test request with multiple uploaded files (nested array)
     */
    public function testFromGlobalsWithMultipleUploadedFiles(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/upload-multiple'
        ];

        // Create temporary files for testing
        $tempFile1 = tempnam(sys_get_temp_dir(), 'test_upload1');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test_upload2');
        file_put_contents($tempFile1, 'content 1');
        file_put_contents($tempFile2, 'content 2');

        $files = [
            'documents' => [
                'name' => ['file1.txt', 'file2.txt'],
                'type' => ['text/plain', 'text/plain'],
                'tmp_name' => [$tempFile1, $tempFile2],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size' => [9, 9]
            ]
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server, [], [], [], $files);

        $uploadedFiles = $request->getUploadedFiles();
        $this->assertArrayHasKey('documents', $uploadedFiles);
        $this->assertIsArray($uploadedFiles['documents']);
        $this->assertCount(2, $uploadedFiles['documents']);

        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['documents'][0]);
        $this->assertEquals('file1.txt', $uploadedFiles['documents'][0]->getClientFilename());

        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['documents'][1]);
        $this->assertEquals('file2.txt', $uploadedFiles['documents'][1]->getClientFilename());

        // Clean up
        unlink($tempFile1);
        unlink($tempFile2);
    }

    /**
     * Test request with default fallback values
     */
    public function testFromGlobalsWithDefaultValues(): void
    {
        $server = [
            'HTTP_HOST' => 'example.com'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('/', $request->getUri()->getPath());
        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    /**
     * Test request with SERVER_NAME fallback
     */
    public function testFromGlobalsWithServerNameFallback(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'SERVER_NAME' => 'fallback.example.com',
            'REQUEST_URI' => '/test'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertEquals('fallback.example.com', $request->getUri()->getHost());
    }

    /**
     * Test request with localhost fallback
     */
    public function testFromGlobalsWithLocalhostFallback(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertEquals('localhost', $request->getUri()->getHost());
    }

    /**
     * Test request with empty body
     */
    public function testFromGlobalsWithEmptyBody(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/empty'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server, [], []);

        $this->assertNull($request->getParsedBody());
    }

    /**
     * Test request with complex URI parsing
     */
    public function testFromGlobalsWithComplexUri(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/path/to/resource?param=value&other=123#fragment',
            'QUERY_STRING' => 'param=value&other=123'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertEquals('/path/to/resource', $request->getUri()->getPath());
        $this->assertEquals('param=value&other=123', $request->getUri()->getQuery());
    }

    /**
     * Test request with malformed URI
     */
    public function testFromGlobalsWithMalformedUri(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => null
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertEquals('/', $request->getUri()->getPath());
    }

    /**
     * Test request with server variables
     */
    public function testFromGlobalsWithServerVariables(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/test',
            'SERVER_PROTOCOL' => '1.1',
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'TestClient/1.0'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $serverParams = $request->getServerParams();
        $this->assertEquals('GET', $serverParams['REQUEST_METHOD']);
        $this->assertEquals('example.com', $serverParams['HTTP_HOST']);
        $this->assertEquals('/test', $serverParams['REQUEST_URI']);
        $this->assertEquals('1.1', $serverParams['SERVER_PROTOCOL']);
        $this->assertEquals('192.168.1.1', $serverParams['REMOTE_ADDR']);
        $this->assertEquals('TestClient/1.0', $serverParams['HTTP_USER_AGENT']);
    }

    /**
     * Test request using actual PHP globals
     */
    public function testFromGlobalsUsingActualGlobals(): void
    {
        // Set up test globals
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'test.example.com',
            'REQUEST_URI' => '/test-globals',
            'HTTP_CONTENT_TYPE' => 'application/json'
        ];
        $_GET = ['query' => 'test'];
        $_POST = ['data' => 'value'];
        $_COOKIE = ['session' => 'abc123'];
        $_FILES = [];

        $request = GlobalsToServerRequestAdapter::fromGlobals();

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('test.example.com', $request->getUri()->getHost());
        $this->assertEquals('/test-globals', $request->getUri()->getPath());
        $this->assertEquals(['query' => 'test'], $request->getQueryParams());
        $this->assertEquals(['data' => 'value'], $request->getParsedBody());
        $this->assertEquals(['session' => 'abc123'], $request->getCookieParams());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
    }

    /**
     * Test header normalization
     */
    public function testFromGlobalsHeaderNormalization(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/test',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
            'HTTP_CACHE_CONTROL' => 'no-cache',
            'HTTP_X_FORWARDED_FOR' => '192.168.1.1',
            'HTTP_X_FORWARDED_PROTO' => 'https'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);

        $this->assertEquals('en-US,en;q=0.9', $request->getHeaderLine('Accept-Language'));
        $this->assertEquals('no-cache', $request->getHeaderLine('Cache-Control'));
        $this->assertEquals('192.168.1.1', $request->getHeaderLine('X-Forwarded-For'));
        $this->assertEquals('https', $request->getHeaderLine('X-Forwarded-Proto'));
    }

    /**
     * Test request with standard ports (should not be included in URI)
     */
    public function testFromGlobalsWithStandardPorts(): void
    {
        // HTTP standard port
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/test',
            'SERVER_PORT' => '80'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);
        $this->assertNull($request->getUri()->getPort());

        // HTTPS standard port
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/test',
            'HTTPS' => 'on',
            'SERVER_PORT' => '443'
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);
        $this->assertNull($request->getUri()->getPort());
    }

    /**
     * Test request with edge cases
     */
    public function testFromGlobalsEdgeCases(): void
    {
        // Empty query string
        $server = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/test',
            'QUERY_STRING' => ''
        ];

        $request = GlobalsToServerRequestAdapter::fromGlobals($server);
        $this->assertEquals('', $request->getUri()->getQuery());

        // No uploaded files
        $request = GlobalsToServerRequestAdapter::fromGlobals($server, [], [], [], []);
        $this->assertEquals([], $request->getUploadedFiles());
    }

    /**
     * Test performance with large data sets
     */
    public function testFromGlobalsPerformance(): void
    {
        // Create large server array
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/performance-test'
        ];

        // Add many headers
        for ($i = 0; $i < 100; $i++) {
            $server["HTTP_HEADER_$i"] = "value$i";
        }

        $startTime = microtime(true);
        $request = GlobalsToServerRequestAdapter::fromGlobals($server);
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertLessThan(50, $duration); // Should process quickly (less than 50ms)
    }
}
