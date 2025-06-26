<?php

namespace Express\Tests\Services;

use PHPUnit\Framework\TestCase;
use Express\Http\Response;

class ResponseTest extends TestCase
{
    private $response;

    protected function setUp(): void
    {
        $this->response = new Response();

        // Reset output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
    }

    protected function tearDown(): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
    }

    public function testResponseInitialization(): void
    {
        $this->assertInstanceOf(Response::class, $this->response);
    }

    public function testStatusMethod(): void
    {
        $result = $this->response->status(404);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame($this->response, $result); // Should return same instance for chaining
    }

    public function testHeaderMethod(): void
    {
        $result = $this->response->header('Content-Type', 'application/json');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame($this->response, $result); // Should return same instance for chaining
    }

    public function testJsonResponse(): void
    {
        $data = ['message' => 'Hello World', 'status' => 'success'];

        $result = $this->response->json($data);
        $output = ob_get_contents();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(json_encode($data), $output);
    }

    public function testTextResponse(): void
    {
        $text = 'Hello World';

        $result = $this->response->text($text);
        $output = ob_get_contents();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($text, $output);
    }

    public function testHtmlResponse(): void
    {
        $html = '<h1>Hello World</h1><p>This is HTML content</p>';

        $result = $this->response->html($html);
        $output = ob_get_contents();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($html, $output);
    }

    public function testMethodChaining(): void
    {
        $data = ['message' => 'Created successfully'];

        $result = $this->response
            ->status(201)
            ->header('X-Custom-Header', 'custom-value')
            ->json($data);

        $output = ob_get_contents();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(json_encode($data), $output);
    }

    public function testComplexJsonResponse(): void
    {
        $complexData = [
            'users' => [
                ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2
            ],
            'meta' => [
                'timestamp' => '2023-01-01T00:00:00Z',
                'version' => '1.0.0'
            ]
        ];

        $this->response->json($complexData);
        $output = ob_get_contents();

        $this->assertEquals(json_encode($complexData), $output);
    }

    public function testEmptyJsonResponse(): void
    {
        $this->response->json([]);
        $output = ob_get_contents();

        $this->assertEquals('[]', $output);
    }

    public function testNullJsonResponse(): void
    {
        $this->response->json(null);
        $output = ob_get_contents();

        $this->assertEquals('null', $output);
    }

    public function testBooleanJsonResponse(): void
    {
        $this->response->json(true);
        $output = ob_get_contents();

        $this->assertEquals('true', $output);

        ob_clean();

        $this->response->json(false);
        $output = ob_get_contents();

        $this->assertEquals('false', $output);
    }

    public function testNumericJsonResponse(): void
    {
        $this->response->json(42);
        $output = ob_get_contents();

        $this->assertEquals('42', $output);

        ob_clean();

        $this->response->json(3.14);
        $output = ob_get_contents();

        $this->assertEquals('3.14', $output);
    }

    public function testStringJsonResponse(): void
    {
        $this->response->json('Hello World');
        $output = ob_get_contents();

        $this->assertEquals('"Hello World"', $output);
    }

    public function testEmptyTextResponse(): void
    {
        $this->response->text('');
        $output = ob_get_contents();

        $this->assertEquals('', $output);
    }

    public function testMultilineTextResponse(): void
    {
        $text = "Line 1\nLine 2\nLine 3";

        $this->response->text($text);
        $output = ob_get_contents();

        $this->assertEquals($text, $output);
    }

    public function testHtmlWithSpecialCharacters(): void
    {
        $html = '<div>Content with &amp; special chars &lt;script&gt;</div>';

        $this->response->html($html);
        $output = ob_get_contents();

        $this->assertEquals($html, $output);
    }

    public function testMultipleHeaders(): void
    {
        $result = $this->response
            ->header('Content-Type', 'application/json')
            ->header('X-API-Version', '1.0')
            ->header('X-Rate-Limit', '1000');

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testStatusCodes(): void
    {
        // Test various HTTP status codes
        $statusCodes = [200, 201, 400, 401, 403, 404, 500];

        foreach ($statusCodes as $code) {
            $result = $this->response->status($code);
            $this->assertInstanceOf(Response::class, $result);
        }
    }
}
