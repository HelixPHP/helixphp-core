<?php

declare(strict_types=1);

namespace Express\Providers;

use Express\Core\Application;
use Psr\Container\ContainerInterface;

/**
 * Container Service Provider
 */
class ContainerServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        // Register the container itself
        $this->app->instance(ContainerInterface::class, $this->app->getContainer());
        $this->app->alias('container', ContainerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return [
            ContainerInterface::class,
            'container'
        ];
    }
}
