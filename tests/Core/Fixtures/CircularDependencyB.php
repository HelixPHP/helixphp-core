<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core\Fixtures;

class CircularDependencyB
{
    public function __construct(CircularDependencyA $_)
    {
        // Intentional circular dependency for testing
    }
}
