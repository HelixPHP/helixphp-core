<?php

declare(strict_types=1);

namespace Helix\Providers;

use Helix\Core\Application;
use Helix\Providers\ServiceProvider;
use Helix\Providers\ExtensionManager;

/**
 * Extension Service Provider
 *
 * Provides extension management capabilities to the application
 */
class ExtensionServiceProvider extends ServiceProvider
{
    /**
     * Register extension services
     */
    public function register(): void
    {
        // Register ExtensionManager as singleton
        $this->app->singleton(
            ExtensionManager::class,
            function () {
                return new ExtensionManager($this->app);
            }
        );

        // Create alias for easier access
        $this->app->getContainer()->alias('extensions', ExtensionManager::class);
    }

    /**
     * Boot extension services
     */
    public function boot(): void
    {
        /** @var ExtensionManager $extensionManager */
        $extensionManager = $this->app->make(ExtensionManager::class);

        // Load extensions from configuration
        $extensionManager->loadFromConfig();
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            ExtensionManager::class,
            'extensions'
        ];
    }
}
