<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PivotPHP\Core\Core\Application;

/**
 * Test server for advanced testing scenarios
 */
class TestServer
{
    private Application $app;
    private array $config;

    public function __construct(Application $app, array $config = [])
    {
        $this->app = $app;
        $this->config = $config;
    }

    public function start(): void
    {
        // Start test server
        // This would be implemented based on testing needs
    }

    public function stop(): void
    {
        // Stop test server
    }

    public function getUrl(): string
    {
        return 'http://localhost:' . ($this->config['port'] ?? 8080);
    }
}
