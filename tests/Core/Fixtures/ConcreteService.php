<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core\Fixtures;

class ConcreteService implements ServiceInterface
{
    public function process(): string
    {
        return 'processed';
    }
}
