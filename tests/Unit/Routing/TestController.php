<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Unit\Routing;

/**
 * Test class for array callable functionality
 */
class TestController
{
    public function index($req, $res): string
    {
        return 'controller index';
    }

    public function show($req, $res): string
    {
        $id = $req->param('id');
        return "controller show: {$id}";
    }

    public static function staticMethod($req, $res): string
    {
        return 'static method';
    }

    public function healthCheck($req, $res): array
    {
        return [
            'status' => 'ok',
            'timestamp' => time(),
            'method' => 'healthCheck'
        ];
    }

    public function withParameters($req, $res): array
    {
        return [
            'user_id' => $req->param('userId'),
            'post_id' => $req->param('postId')
        ];
    }
}
