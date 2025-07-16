<?php

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Response;

class ResponseTest extends TestCase
{
    public function testBasicResponseCreation(): void
    {
        $response = new Response();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testResponseWithStatus(): void
    {
        $response = new Response();
        $response->status(404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testResponseJson(): void
    {
        $response = new Response();
        $data = ['message' => 'success'];

        $result = $response->json($data);

        $this->assertSame($response, $result);
        $this->assertEquals('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    public function testResponseText(): void
    {
        $response = new Response();

        $result = $response->text('Hello World');

        $this->assertSame($response, $result);
        $this->assertEquals('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    public function testResponseHtml(): void
    {
        $response = new Response();

        $result = $response->html('<h1>Hello</h1>');

        $this->assertSame($response, $result);
        $this->assertEquals('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    public function testResponseWithHeader(): void
    {
        $response = new Response();

        $result = $response->header('X-Custom', 'value');

        $this->assertSame($response, $result);
        $this->assertEquals('value', $response->getHeaderLine('X-Custom'));
    }

    public function testResponseRedirect(): void
    {
        $response = new Response();

        $result = $response->redirect('/new-path');

        $this->assertSame($response, $result);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/new-path', $response->getHeaderLine('Location'));
    }

    public function testResponseRedirectWithCustomStatus(): void
    {
        $response = new Response();

        $result = $response->redirect('/new-path', 301);

        $this->assertSame($response, $result);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/new-path', $response->getHeaderLine('Location'));
    }
}
