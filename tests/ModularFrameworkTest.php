<?php

use PHPUnit\Framework\TestCase;
use Express\Http\Request;
use Express\Http\Response;

class ModularFrameworkTest extends TestCase
{
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

        ob_start();
        $response->json(['test' => 'data']);
        $output = ob_get_clean();

        $this->assertEquals('{"test":"data"}', $output);
    }

    /**
     * @group streaming
     */
    public function testStreamingResponse()
    {
        $response = new Response();

        $result = $response->startStream('text/plain');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($response->isStreaming());
    }
}
