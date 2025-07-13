<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core\Fixtures;

class ClassWithParameters
{
    public string $param;

    public function __construct(string $param = 'default')
    {
        $this->param = $param;
    }
}
