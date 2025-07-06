<?php

namespace Helix\Tests;

use PHPUnit\Framework\TestCase;
use Helix\Http\Request;
use Helix\Http\Response;

class ModularFrameworkTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean any output buffers that might have been opened, but keep PHPUnit's own buffer
        while (ob_get_level() > 1) {
            ob_end_clean();
        }

        // Clear current buffer content if any
        if (ob_get_level() && ob_get_length()) {
            ob_clean();
        }
    }

    public function testHttpRequestBasic()
    {
        $request = new Request('GET', '/', '/');

        $this->assertEquals('GET', $request->method);
        $this->assertEquals('/', $request->path);
    }

    public function testHttpResponse()
    {
        $response = new Response();

        $result = $response->status(200);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testRequestWithParameters()
    {
        $request = new Request('GET', '/users/:id', '/users/123');

        $this->assertEquals(123, $request->param('id'));
    }

    public function testResponseJson()
    {
        $response = new Response();
        $response->setTestMode(true); // Ativar modo teste

        $response->json(['test' => 'data']);

        $this->assertEquals('{"test":"data"}', $response->getBody());
    }

    /**
     * @group streaming
     */
    public function testStreamingResponse()
    {
        $response = new Response();
        $response->setTestMode(true); // Ativar modo teste

        $result = $response->startStream('text/plain');

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($response->isStreaming());
    }
}
