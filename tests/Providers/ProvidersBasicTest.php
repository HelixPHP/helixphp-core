<?php

namespace PivotPHP\Core\Tests\Providers;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Providers\ContainerServiceProvider;
use PivotPHP\Core\Providers\EventServiceProvider;
use PivotPHP\Core\Providers\LoggingServiceProvider;
use PivotPHP\Core\Providers\HookServiceProvider;
use PivotPHP\Core\Providers\ExtensionServiceProvider;
use PivotPHP\Core\Providers\ServiceProvider;
use PivotPHP\Core\Core\Application;

class ProvidersBasicTest extends TestCase
{
    public function testContainerServiceProviderCanBeInstantiated(): void
    {
        $app = $this->createMock(Application::class);
        $provider = new ContainerServiceProvider($app);

        $this->assertInstanceOf(ContainerServiceProvider::class, $provider);
        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    public function testEventServiceProviderCanBeInstantiated(): void
    {
        $app = $this->createMock(Application::class);
        $provider = new EventServiceProvider($app);

        $this->assertInstanceOf(EventServiceProvider::class, $provider);
        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    public function testLoggingServiceProviderCanBeInstantiated(): void
    {
        $app = $this->createMock(Application::class);
        $provider = new LoggingServiceProvider($app);

        $this->assertInstanceOf(LoggingServiceProvider::class, $provider);
        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    public function testHookServiceProviderCanBeInstantiated(): void
    {
        $app = $this->createMock(Application::class);
        $provider = new HookServiceProvider($app);

        $this->assertInstanceOf(HookServiceProvider::class, $provider);
        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    public function testExtensionServiceProviderCanBeInstantiated(): void
    {
        $app = $this->createMock(Application::class);
        $provider = new ExtensionServiceProvider($app);

        $this->assertInstanceOf(ExtensionServiceProvider::class, $provider);
        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    public function testServiceProviderAbstractClass(): void
    {
        $app = $this->createMock(Application::class);
        $provider = new class ($app) extends ServiceProvider {
            public function register(): void
            {
                // Test implementation
            }
        };

        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    public function testServiceProviderProvidesMethods(): void
    {
        $app = $this->createMock(Application::class);
        $provider = new class ($app) extends ServiceProvider {
            public function register(): void
            {
                // Test implementation
            }

            public function provides(): array
            {
                return ['test_service'];
            }
        };

        $services = $provider->provides();

        $this->assertIsArray($services);
        $this->assertContains('test_service', $services);
    }

    public function testServiceProviderBootMethod(): void
    {
        $app = $this->createMock(Application::class);
        $provider = new class ($app) extends ServiceProvider {
            public function register(): void
            {
                // Test implementation
            }

            public function boot(): void
            {
                // Test implementation
            }
        };

        $provider->register();
        $provider->boot();

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testServiceProviderIsDeferredMethod(): void
    {
        $app = $this->createMock(Application::class);
        $provider = new class ($app) extends ServiceProvider {
            public function register(): void
            {
                // Test implementation
            }

            public function isDeferred(): bool
            {
                return true;
            }
        };

        $this->assertTrue($provider->isDeferred());
    }
}
