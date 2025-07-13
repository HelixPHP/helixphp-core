<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core\Fixtures;

class TestProcessor
{
    public TestLogger $logger;
    public TestConfig $config;

    public function __construct(TestLogger $logger, TestConfig $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }
}
