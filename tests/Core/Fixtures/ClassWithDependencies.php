<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core\Fixtures;

class ClassWithDependencies
{
    public SimpleClassWithoutDependencies $dependency;

    public function __construct(SimpleClassWithoutDependencies $dependency)
    {
        $this->dependency = $dependency;
    }
}
