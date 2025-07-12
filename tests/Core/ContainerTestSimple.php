<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Container;
use ReflectionProperty;
use stdClass;

/**
 * Simplified Container test to debug the issue
 */
class ContainerTestSimple extends TestCase
{
    public function testContainerBasicFunctionality(): void
    {
        $container = Container::getInstance();

        // Test basic container creation
        $this->assertInstanceOf(Container::class, $container);

        // Test container registers itself
        $resolved = $container->make(Container::class);
        $this->assertSame($container, $resolved);
    }

    public function testBasicBinding(): void
    {
        $container = Container::getInstance();

        // Reset the container state by flushing it
        $container->flush();

        // Test basic class binding
        $container->bind('test', stdClass::class);

        // Check if it's bound
        $this->assertTrue($container->bound('test'));

        // Basic binding test without debug output

        // Try to resolve it
        $instance = $container->make('test');
        $this->assertInstanceOf(stdClass::class, $instance);
    }

    public function testDebugContainerState(): void
    {
        $container = Container::getInstance();
        $container->flush();

        $container->bind('debug_test', stdClass::class);

        // Access the private bindings property to see the actual structure
        $reflection = new \ReflectionClass($container);
        $bindingsProperty = $reflection->getProperty('bindings');
        $bindingsProperty->setAccessible(true);
        $bindings = $bindingsProperty->getValue($container);

        // Verify binding structure is correct
        $this->assertArrayHasKey('debug_test', $bindings);
        $this->assertArrayHasKey('concrete', $bindings['debug_test']);
        $this->assertArrayHasKey('singleton', $bindings['debug_test']);
        $this->assertArrayHasKey('instance', $bindings['debug_test']);

        $this->assertTrue(true); // Just complete the test
    }
}
