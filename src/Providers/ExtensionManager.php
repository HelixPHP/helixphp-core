<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Providers\ServiceProvider;
use Psr\Container\ContainerInterface;
use Composer\Autoload\ClassLoader;

/**
 * Extension Manager for auto-discovery and management of plugins/extensions
 *
 * This class handles automatic discovery of service providers from composer packages,
 * plugin registration, and extension lifecycle management.
 */
class ExtensionManager
{
    /**
     * The application instance
     */
    protected Application $app;

    /**
     * Container instance
     */
    protected ContainerInterface $container;

    /**
     * Registered extensions
     *
     * @var array<string, array{provider: string, config: array, enabled: bool}>
     */
    protected array $extensions = [];

    /**
     * Auto-discovered providers
     *
     * @var array<string>
     */
    protected array $discoveredProviders = [];

    /**
     * Create extension manager instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->container = $app->getContainer();
    }

    /**
     * Auto-discover service providers from composer packages
     *
     * @return array<string>
     */
    public function discoverProviders(): array
    {
        $this->discoveredProviders = [];

        // Get composer autoloader
        $autoloader = $this->getComposerAutoloader();
        if (!$autoloader) {
            return [];
        }

        // Get all composer packages
        $packages = $this->getInstalledPackages();

        foreach ($packages as $package) {
            $providers = $this->discoverProvidersFromPackage($package);
            $this->discoveredProviders = array_merge($this->discoveredProviders, $providers);
        }

        return $this->discoveredProviders;
    }

    /**
     * Register discovered providers automatically
     */
    public function registerDiscoveredProviders(): void
    {
        foreach ($this->discoveredProviders as $provider) {
            if (class_exists($provider) && is_subclass_of($provider, ServiceProvider::class)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Register an extension manually
     */
    public function registerExtension(string $name, string $provider, array $config = []): void
    {
        $this->extensions[$name] = [
            'provider' => $provider,
            'config' => $config,
            'enabled' => true
        ];

        if (class_exists($provider)) {
            $this->app->register($provider);
        }
    }

    /**
     * Enable an extension
     */
    public function enableExtension(string $name): bool
    {
        if (!isset($this->extensions[$name])) {
            return false;
        }

        $this->extensions[$name]['enabled'] = true;

        $provider = $this->extensions[$name]['provider'];
        if (class_exists($provider)) {
            $this->app->register($provider);
        }

        return true;
    }

    /**
     * Disable an extension
     */
    public function disableExtension(string $name): bool
    {
        if (!isset($this->extensions[$name])) {
            return false;
        }

        $this->extensions[$name]['enabled'] = false;
        return true;
    }

    /**
     * Get list of registered extensions
     *
     * @return array<string, array{provider: string, config: array, enabled: bool}>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Get enabled extensions only
     *
     * @return array<string, array{provider: string, config: array, enabled: bool}>
     */
    public function getEnabledExtensions(): array
    {
        return array_filter($this->extensions, fn($ext) => $ext['enabled']);
    }

    /**
     * Check if extension is registered
     */
    public function hasExtension(string $name): bool
    {
        return isset($this->extensions[$name]);
    }

    /**
     * Check if extension is enabled
     */
    public function isExtensionEnabled(string $name): bool
    {
        return isset($this->extensions[$name]) && $this->extensions[$name]['enabled'];
    }

    /**
     * Get composer autoloader instance
     */
    protected function getComposerAutoloader(): ?ClassLoader
    {
        $autoloadFiles = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../../../autoload.php',
            __DIR__ . '/../../../../autoload.php',
            __DIR__ . '/../../../../../autoload.php',
        ];

        foreach ($autoloadFiles as $file) {
            if (file_exists($file)) {
                /** @var ClassLoader $autoloader */
                $autoloader = require $file;
                return $autoloader;
            }
        }

        return null;
    }

    /**
     * Get installed composer packages
     *
     * @return array<string>
     */
    protected function getInstalledPackages(): array
    {
        $packages = [];

        $installedPath = $this->findInstalledJsonPath();
        if (!$installedPath || !file_exists($installedPath)) {
            return [];
        }

        $content = file_get_contents($installedPath);
        if ($content === false) {
            return [];
        }

        $installedData = json_decode($content, true);
        if (!is_array($installedData) || !isset($installedData['packages']) || !is_array($installedData['packages'])) {
            return [];
        }

        foreach ($installedData['packages'] as $package) {
            if (is_array($package) && isset($package['name']) && is_string($package['name'])) {
                $packages[] = $package['name'];
            }
        }

        return $packages;
    }

    /**
     * Find installed.json path
     */
    protected function findInstalledJsonPath(): ?string
    {
        $paths = [
            __DIR__ . '/../../vendor/composer/installed.json',
            __DIR__ . '/../../../composer/installed.json',
            __DIR__ . '/../../../../composer/installed.json',
            __DIR__ . '/../../../../../composer/installed.json',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Discover providers from a specific package
     *
     * @return array<string>
     */
    protected function discoverProvidersFromPackage(string $packageName): array
    {
        $providers = [];

        // Look for package-specific configuration
        $packagePath = $this->getPackagePath($packageName);
        if (!$packagePath) {
            return [];
        }

        // Check composer.json for express-php providers
        $composerFile = $packagePath . '/composer.json';
        if (file_exists($composerFile)) {
            $content = file_get_contents($composerFile);
            if ($content === false) {
                return [];
            }

            $composerData = json_decode($content, true);

            if (
                is_array($composerData) &&
                isset($composerData['extra']) && is_array($composerData['extra']) &&
                isset($composerData['extra']['express-php']) &&
                is_array($composerData['extra']['express-php']) &&
                isset($composerData['extra']['express-php']['providers']) &&
                is_array($composerData['extra']['express-php']['providers'])
            ) {
                $providers = array_merge(
                    $providers,
                    $composerData['extra']['express-php']['providers']
                );
            }
        }

        // Auto-discover by namespace convention
        $autoProviders = $this->autoDiscoverProviders($packagePath, $packageName);
        $providers = array_merge($providers, $autoProviders);

        return array_unique($providers);
    }

    /**
     * Get package installation path
     */
    protected function getPackagePath(string $packageName): ?string
    {
        $vendorPath = __DIR__ . '/../../vendor/' . $packageName;
        if (is_dir($vendorPath)) {
            return $vendorPath;
        }

        return null;
    }

    /**
     * Auto-discover providers by namespace convention
     *
     * @return array<string>
     */
    protected function autoDiscoverProviders(string $packagePath, string $packageName): array
    {
        $providers = [];

        // Convert package name to namespace
        $parts = explode('/', $packageName);
        if (count($parts) !== 2) {
            return [];
        }

        $vendor = str_replace(['-', '_'], ['', ''], ucwords($parts[0], '-_'));
        $package = str_replace(['-', '_'], ['', ''], ucwords($parts[1], '-_'));

        // Common provider locations
        $possibleProviders = [
            "{$vendor}\\{$package}\\ExpressServiceProvider",
            "{$vendor}\\{$package}\\ServiceProvider",
            "{$vendor}\\{$package}\\Providers\\ExpressServiceProvider",
            "{$vendor}\\{$package}\\Providers\\ServiceProvider",
        ];

        foreach ($possibleProviders as $providerClass) {
            if (
                class_exists($providerClass) &&
                is_subclass_of($providerClass, ServiceProvider::class)
            ) {
                $providers[] = $providerClass;
            }
        }

        return $providers;
    }

    /**
     * Load extensions from configuration
     */
    public function loadFromConfig(): void
    {
        if (!$this->container->has('config')) {
            return;
        }

        /** @var \PivotPHP\Core\Core\Config $config */
        $config = $this->container->get('config');

        // Load from app.extensions config
        $extensions = $config->get('app.extensions', []);
        if (is_array($extensions)) {
            foreach ($extensions as $name => $extensionConfig) {
                if (
                    is_string($name) &&
                    is_array($extensionConfig) &&
                    isset($extensionConfig['provider']) &&
                    is_string($extensionConfig['provider'])
                ) {
                    $this->registerExtension(
                        $name,
                        $extensionConfig['provider'],
                        is_array($extensionConfig['config'] ?? null) ?
                            $extensionConfig['config'] : []
                    );
                }
            }
        }

        // Auto-discovery if enabled
        if ($config->get('app.extensions.auto_discover_providers', true)) {
            $this->discoverProviders();
            $this->registerDiscoveredProviders();
        }
    }

    /**
     * Get extension statistics
     *
     * @return array{total: int, enabled: int, disabled: int, discovered: int}
     */
    public function getStats(): array
    {
        $enabled = count($this->getEnabledExtensions());
        $total = count($this->extensions);

        return [
            'total' => $total,
            'enabled' => $enabled,
            'disabled' => $total - $enabled,
            'discovered' => count($this->discoveredProviders)
        ];
    }
}
