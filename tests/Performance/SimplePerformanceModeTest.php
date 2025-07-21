<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Performance;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Performance\SimplePerformanceMode;

/**
 * Tests for SimplePerformanceMode
 *
 * Following the "less is more" principle, this test focuses on essential
 * functionality without over-engineering.
 */
class SimplePerformanceModeTest extends TestCase
{
    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        SimplePerformanceMode::disable();
        parent::tearDown();
    }

    /**
     * Test development profile
     */
    public function testDevelopmentProfile(): void
    {
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_DEVELOPMENT);

        $this->assertTrue(SimplePerformanceMode::isEnabled());
        $this->assertEquals(SimplePerformanceMode::PROFILE_DEVELOPMENT, SimplePerformanceMode::getCurrentMode());

        $status = SimplePerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
        $this->assertEquals(SimplePerformanceMode::PROFILE_DEVELOPMENT, $status['mode']);
        $this->assertFalse($status['pool_enabled']);
    }

    /**
     * Test production profile
     */
    public function testProductionProfile(): void
    {
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_PRODUCTION);

        $this->assertTrue(SimplePerformanceMode::isEnabled());
        $this->assertEquals(SimplePerformanceMode::PROFILE_PRODUCTION, SimplePerformanceMode::getCurrentMode());

        $status = SimplePerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
        $this->assertEquals(SimplePerformanceMode::PROFILE_PRODUCTION, $status['mode']);
        $this->assertTrue($status['pool_enabled']);
    }

    /**
     * Test test profile
     */
    public function testTestProfile(): void
    {
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_TEST);

        $this->assertTrue(SimplePerformanceMode::isEnabled());
        $this->assertEquals(SimplePerformanceMode::PROFILE_TEST, SimplePerformanceMode::getCurrentMode());

        $status = SimplePerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
        $this->assertEquals(SimplePerformanceMode::PROFILE_TEST, $status['mode']);
        $this->assertFalse($status['pool_enabled']);
    }

    /**
     * Test default profile (production)
     */
    public function testDefaultProfile(): void
    {
        SimplePerformanceMode::enable();

        $this->assertTrue(SimplePerformanceMode::isEnabled());
        $this->assertEquals(SimplePerformanceMode::PROFILE_PRODUCTION, SimplePerformanceMode::getCurrentMode());
    }

    /**
     * Test disable functionality
     */
    public function testDisable(): void
    {
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_PRODUCTION);
        $this->assertTrue(SimplePerformanceMode::isEnabled());

        SimplePerformanceMode::disable();

        $this->assertFalse(SimplePerformanceMode::isEnabled());
        $this->assertNull(SimplePerformanceMode::getCurrentMode());

        $status = SimplePerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);
        $this->assertNull($status['mode']);
        $this->assertFalse($status['pool_enabled']);
    }

    /**
     * Test initial state
     */
    public function testInitialState(): void
    {
        $this->assertFalse(SimplePerformanceMode::isEnabled());
        $this->assertNull(SimplePerformanceMode::getCurrentMode());

        $status = SimplePerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);
        $this->assertNull($status['mode']);
        $this->assertFalse($status['pool_enabled']);
    }

    /**
     * Test multiple enable/disable cycles
     */
    public function testMultipleEnableDisableCycles(): void
    {
        // First cycle
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_DEVELOPMENT);
        $this->assertEquals(SimplePerformanceMode::PROFILE_DEVELOPMENT, SimplePerformanceMode::getCurrentMode());

        SimplePerformanceMode::disable();
        $this->assertNull(SimplePerformanceMode::getCurrentMode());

        // Second cycle with different profile
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_PRODUCTION);
        $this->assertEquals(SimplePerformanceMode::PROFILE_PRODUCTION, SimplePerformanceMode::getCurrentMode());

        SimplePerformanceMode::disable();
        $this->assertNull(SimplePerformanceMode::getCurrentMode());
    }

    /**
     * Test profile switching
     */
    public function testProfileSwitching(): void
    {
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_DEVELOPMENT);
        $this->assertEquals(SimplePerformanceMode::PROFILE_DEVELOPMENT, SimplePerformanceMode::getCurrentMode());

        // Switch to production
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_PRODUCTION);
        $this->assertEquals(SimplePerformanceMode::PROFILE_PRODUCTION, SimplePerformanceMode::getCurrentMode());

        // Switch to test
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_TEST);
        $this->assertEquals(SimplePerformanceMode::PROFILE_TEST, SimplePerformanceMode::getCurrentMode());
    }

    /**
     * Test status consistency
     */
    public function testStatusConsistency(): void
    {
        // Test disabled state
        $status = SimplePerformanceMode::getStatus();
        $this->assertEquals(SimplePerformanceMode::isEnabled(), $status['enabled']);
        $this->assertEquals(SimplePerformanceMode::getCurrentMode(), $status['mode']);

        // Test enabled state
        SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_PRODUCTION);
        $status = SimplePerformanceMode::getStatus();
        $this->assertEquals(SimplePerformanceMode::isEnabled(), $status['enabled']);
        $this->assertEquals(SimplePerformanceMode::getCurrentMode(), $status['mode']);

        // Test disabled again
        SimplePerformanceMode::disable();
        $status = SimplePerformanceMode::getStatus();
        $this->assertEquals(SimplePerformanceMode::isEnabled(), $status['enabled']);
        $this->assertEquals(SimplePerformanceMode::getCurrentMode(), $status['mode']);
    }
}
