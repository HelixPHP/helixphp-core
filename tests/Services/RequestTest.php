<?php

namespace Express\Tests\Services;

use PHPUnit\Framework\TestCase;
use Express\Services\Request;
use Express\Services\HeaderRequest;
use InvalidArgumentException;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset global variables
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER = [];
    }

    public function testRequestInitialization(): void
    {
        $request = new Request('GET', '/users/:id', '/users/123');
        
        $this->assertEquals('GET', $request->method);
        $this->assertEquals('/users/:id', $request->path);
        $this->assertEquals('/users/123/', $request->pathCallable);
        $this->assertInstanceOf('stdClass', $request->params);
        $this->assertInstanceOf('stdClass', $request->query);
        $this->assertInstanceOf('stdClass', $request->body);
        $this->assertInstanceOf(HeaderRequest::class, $request->headers);
    }

    public function testMethodNormalization(): void
    {
        $request = new Request('post', '/users', '/users');
        $this->assertEquals('POST', $request->method);
        
        $request = new Request('PUT', '/users/:id', '/users/123');
        $this->assertEquals('PUT', $request->method);
    }

    public function testPathCallableSlashNormalization(): void
    {
        $request = new Request('GET', '/users', '/users');
        $this->assertEquals('/users/', $request->pathCallable);
        
        $request = new Request('GET', '/users/', '/users/');
        $this->assertEquals('/users/', $request->pathCallable);
    }

    public function testParameterExtraction(): void
    {
        $_GET = ['page' => '1', 'limit' => '10'];
        
        $request = new Request('GET', '/users/:id', '/users/123');
        
        $this->assertEquals('1', $request->query->page);
        $this->assertEquals('10', $request->query->limit);
    }

    public function testBodyParsing(): void
    {
        $_POST = ['name' => 'John', 'email' => 'john@example.com'];
        
        $request = new Request('POST', '/users', '/users');
        
        $this->assertEquals('John', $request->body->name);
        $this->assertEquals('john@example.com', $request->body->email);
    }

    public function testFilesHandling(): void
    {
        $_FILES = [
            'avatar' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php123',
                'error' => 0,
                'size' => 12345
            ]
        ];
        
        $request = new Request('POST', '/upload', '/upload');
        
        $this->assertArrayHasKey('avatar', $request->files);
        $this->assertEquals('avatar.jpg', $request->files['avatar']['name']);
    }

    public function testInvalidPropertyAccess(): void
    {
        $request = new Request('GET', '/test', '/test');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Property invalid does not exist in Route class');
        
        $request->invalid;
    }

    public function testRouteParameterParsing(): void
    {
        $request = new Request('GET', '/users/:id/posts/:postId', '/users/123/posts/456');
        
        // O método parseRoute deve extrair os parâmetros
        $this->assertInstanceOf('stdClass', $request->params);
    }

    public function testEmptyQueryParameters(): void
    {
        $_GET = [];
        
        $request = new Request('GET', '/users', '/users');
        
        $this->assertInstanceOf('stdClass', $request->query);
        $this->assertEmpty((array)$request->query);
    }

    public function testEmptyBodyParameters(): void
    {
        $_POST = [];
        
        $request = new Request('POST', '/users', '/users');
        
        $this->assertInstanceOf('stdClass', $request->body);
        $this->assertEmpty((array)$request->body);
    }

    public function testComplexRoutePattern(): void
    {
        $request = new Request('GET', '/api/v1/users/:userId/posts/:postId/comments', '/api/v1/users/123/posts/456/comments');
        
        $this->assertEquals('GET', $request->method);
        $this->assertEquals('/api/v1/users/:userId/posts/:postId/comments', $request->path);
        $this->assertEquals('/api/v1/users/123/posts/456/comments/', $request->pathCallable);
    }

    public function testSpecialCharactersInPath(): void
    {
        $request = new Request('GET', '/search', '/search');
        
        $this->assertEquals('/search/', $request->pathCallable);
    }

    public function testRequestWithArrayParameters(): void
    {
        $_GET = [
            'tags' => ['php', 'javascript'],
            'filters' => ['active' => 'true', 'category' => 'tech']
        ];
        
        $request = new Request('GET', '/posts', '/posts');
        
        $this->assertIsArray($request->query->tags);
        $this->assertEquals(['php', 'javascript'], $request->query->tags);
    }
}
