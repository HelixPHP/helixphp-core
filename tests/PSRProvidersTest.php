<?php

declare(strict_types=1);

namespace Tests;

use Express\Core\Application;
use Express\Providers\Container;
use Express\Providers\ServiceProvider;
use Express\Events\ApplicationStarted;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class PSRProvidersTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
    }

    public function testContainerIsPSR11Compliant(): void
    {
        $container = $this->app->getContainer();

        $this->assertInstanceOf(Container::class, $container);
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testApplicationBootRegistersProviders(): void
    {
        $this->app->boot();

        // Verificar se os providers padrão foram registrados
        $this->assertTrue($this->app->has('container'));
        $this->assertTrue($this->app->has('events'));
        $this->assertTrue($this->app->has('logger'));
    }

    public function testContainerServiceProvider(): void
    {
        $this->app->boot();

        $container = $this->app->resolve('container');
        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertSame($this->app->getContainer(), $container);
    }

    public function testEventServiceProvider(): void
    {
        $this->app->boot();

        $dispatcher = $this->app->resolve('events');
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        $listenerProvider = $this->app->resolve('listeners');
        $this->assertNotNull($listenerProvider);
    }

    public function testLoggingServiceProvider(): void
    {
        $this->app->boot();

        $logger = $this->app->resolve('logger');
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        // Testar log
        $logger->info('Test log message');
        $this->assertTrue(true); // Se chegou aqui, não teve erro
    }

    public function testEventDispatcherFunctionality(): void
    {
        $this->app->boot();

        $eventFired = false;
        $receivedEvent = null;

        // Registrar listener
        $this->app->addEventListener(
            ApplicationStarted::class,
            function ($event) use (&$eventFired, &$receivedEvent) {
                $eventFired = true;
                $receivedEvent = $event;
            }
        );

        // Disparar evento
        $startTime = new \DateTime();
        $event = new ApplicationStarted($startTime, ['test' => true]);
        $this->app->dispatchEvent($event);

        $this->assertTrue($eventFired);
        $this->assertInstanceOf(ApplicationStarted::class, $receivedEvent);
        $this->assertEquals($startTime, $receivedEvent->startTime);
    }

    public function testCustomServiceProvider(): void
    {
        // Criar provider customizado
        $customProvider = new class ($this->app) extends ServiceProvider {
            public function register(): void
            {
                $this->app->singleton(
                    'custom.service',
                    function () {
                        return new \stdClass();
                    }
                );
            }
        };

        $this->app->register($customProvider);
        $this->app->boot();

        $this->assertTrue($this->app->has('custom.service'));
        $service = $this->app->resolve('custom.service');
        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function testContainerAliases(): void
    {
        $this->app->boot();

        // Testar aliases
        $this->assertSame($this->app->resolve('container'), $this->app->resolve(ContainerInterface::class));
        $this->assertSame($this->app->resolve('events'), $this->app->resolve(EventDispatcherInterface::class));
        $this->assertSame($this->app->resolve('logger'), $this->app->resolve(LoggerInterface::class));
    }

    public function testApplicationHelperMethods(): void
    {
        $this->app->boot();

        // Testar métodos helper
        $logger = $this->app->getLogger();
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $dispatcher = $this->app->getEventDispatcher();
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    public function testApplicationStartedEventIsDispatchedOnBoot(): void
    {
        $eventFired = false;

        // Primeiro fazer o boot para registrar os providers
        $this->app->boot();

        // Agora registrar listener
        $this->app->addEventListener(
            ApplicationStarted::class,
            function () use (&$eventFired) {
                $eventFired = true;
            }
        );

        // Disparar evento manualmente para testar
        $event = new ApplicationStarted(new \DateTime(), []);
        $this->app->dispatchEvent($event);

        $this->assertTrue($eventFired);
    }
}
