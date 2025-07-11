<?php

namespace PivotPHP\Core\Tests\Security;

class MockResponse
{
    public $statusCode = 200;
    public $headers = [];
    public $body = '';

    public function status($code)
    {
        $this->statusCode = $code;
        return $this;
    }

    public function json($data)
    {
        $this->body = json_encode($data);
        return $this;
    }

    public function send($data)
    {
        $this->body = $data;
        return $this;
    }
}
