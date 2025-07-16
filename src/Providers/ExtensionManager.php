<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Core\Core\Application;

/**
 * Extension Manager
 *
 * Simple and effective extension management for the microframework.
 * Provides basic extension functionality without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class ExtensionManager
{
    /**
     * Registered extensions
     */
    private array $extensions = [];

    /**
     * Application instance
     */
    private Application $app;

    /**
     * Constructor
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register an extension
     */
    public function register(string $name, callable $extension): void
    {
        $this->extensions[$name] = $extension;
    }

    /**
     * Get registered extension
     */
    public function get(string $name): ?callable
    {
        return $this->extensions[$name] ?? null;
    }

    /**
     * Check if extension exists
     */
    public function has(string $name): bool
    {
        return isset($this->extensions[$name]);
    }

    /**
     * Get all registered extensions
     */
    public function all(): array
    {
        return $this->extensions;
    }

    /**
     * Remove an extension
     */
    public function remove(string $name): void
    {
        unset($this->extensions[$name]);
    }

    /**
     * Clear all extensions
     */
    public function clear(): void
    {
        $this->extensions = [];
    }

    /**
     * Get extension count
     */
    public function count(): int
    {
        return count($this->extensions);
    }

    /**
     * Execute an extension if it exists
     */
    public function execute(string $name, ...$args): mixed
    {
        if (!$this->has($name)) {
            return null;
        }

        return ($this->extensions[$name])(...$args);
    }

    /**
     * Get application instance
     */
    public function getApp(): Application
    {
        return $this->app;
    }
}
