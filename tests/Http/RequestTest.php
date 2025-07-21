<?php

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class RequestTest extends TestCase
{
    public function testBasicRequestCreation(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getPath());
    }

    public function testRequestGetMethod(): void
    {
        $request = new Request('POST', '/test', '/test');

        $this->assertEquals('POST', $request->getMethod());
    }

    public function testRequestGetPath(): void
    {
        $request = new Request('GET', '/users/123', '/users/123');

        $this->assertEquals('/users/123', $request->getPath());
    }

    public function testRequestParamWithDefault(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertEquals('default', $request->param('missing', 'default'));
    }

    public function testRequestGetWithDefault(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertEquals('default', $request->get('missing', 'default'));
    }

    public function testRequestHasHeaders(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertIsObject($request->headers);
    }

    public function testRequestHasParams(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertIsObject($request->params);
    }

    public function testRequestHasQuery(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertIsObject($request->query);
    }

    public function testRequestHasBody(): void
    {
        $request = new Request('POST', '/test', '/test');

        $this->assertIsObject($request->body);
    }
}
