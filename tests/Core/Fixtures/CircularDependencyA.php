<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core\Fixtures;

class CircularDependencyA
{
    public function __construct(CircularDependencyB $_)
    {
        // Intentional circular dependency for testing
    }
}
