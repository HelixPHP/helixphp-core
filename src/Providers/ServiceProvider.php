<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Core\Core\Application;

/**
 * Base Service Provider class
 *
 * Service providers are the central place to configure your application.
 * All core services and your application services should be registered here.
 */
abstract class ServiceProvider
{
    /**
     * The application instance
     */
    protected Application $app;

    /**
     * Create a new service provider instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register services into the container
     */
    abstract public function register(): void;

    /**
     * Bootstrap services after all providers have been registered
     */
    public function boot(): void
    {
        // Override in subclasses if needed
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Determine if the provider is deferred
     */
    public function isDeferred(): bool
    {
        return false;
    }
}
