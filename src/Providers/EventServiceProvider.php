<?php

declare(strict_types=1);

namespace Express\Providers;

use Express\Core\Application;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Event Service Provider
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        // Register listener provider
        $this->app->singleton(
            ListenerProviderInterface::class,
            function () {
                return new ListenerProvider();
            }
        );

        // Register event dispatcher
        $this->app->singleton(
            EventDispatcherInterface::class,
            function () {
            /** @var ListenerProviderInterface $listenerProvider */
                $listenerProvider = $this->app->resolve(ListenerProviderInterface::class);
                return new EventDispatcher($listenerProvider);
            }
        );

        // Aliases
        $this->app->alias('events', EventDispatcherInterface::class);
        $this->app->alias('listeners', ListenerProviderInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return [
            EventDispatcherInterface::class,
            ListenerProviderInterface::class,
            'events',
            'listeners'
        ];
    }
}
