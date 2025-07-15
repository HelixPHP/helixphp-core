<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Environment;

class EnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear environment cache before each test
        Environment::clearCache();

        // Store original environment values
        $this->originalEnv = $_ENV;
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        // Restore original environment values
        $_ENV = $this->originalEnv;
        $_SERVER = $this->originalServer;

        // Clear cache after test
        Environment::clearCache();
    }

    public function testIsDevelopmentWithEnvironment(): void
    {
        $_ENV['APP_ENV'] = 'development';
        Environment::clearCache();

        $this->assertTrue(Environment::isDevelopment());
        $this->assertFalse(Environment::isProduction());
    }

    public function testIsDevelopmentWithDebugFlag(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['APP_DEBUG'] = 'true';
        Environment::clearCache();

        $this->assertTrue(Environment::isDevelopment());
        $this->assertTrue(Environment::isDebug());
    }

    public function testGetEnvironmentDefault(): void
    {
        // Clear any environment variables that might affect the test
        unset($_ENV['APP_ENV'], $_ENV['APP_DEBUG']);
        unset($_SERVER['APP_ENV'], $_SERVER['APP_DEBUG']);
        Environment::clearCache();

        // Should default to production environment
        $this->assertEquals('production', Environment::getEnvironment());
        $this->assertTrue(Environment::isProduction());
    }

    public function testIsProductionWhenExplicitlySet(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['APP_DEBUG'] = 'false';

        // Store and disable display_errors to ensure production detection
        $originalDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');

        Environment::clearCache();

        try {
            $this->assertTrue(Environment::isProduction());
            $this->assertFalse(Environment::isDevelopment());
            $this->assertEquals('production', Environment::getEnvironment());
        } finally {
            // Restore original setting
            ini_set('display_errors', $originalDisplayErrors);
        }
    }

    public function testIsTesting(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['APP_DEBUG'] = 'false';

        // Store and disable display_errors to prevent development mode detection
        $originalDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');

        Environment::clearCache();

        try {
            $this->assertTrue(Environment::isTesting());
            $this->assertFalse(Environment::isDevelopment());
            $this->assertFalse(Environment::isProduction());
        } finally {
            // Restore original setting
            ini_set('display_errors', $originalDisplayErrors);
        }
    }

    public function testIsDebugWithDifferentFormats(): void
    {
        // Test with string 'true'
        $_ENV['APP_DEBUG'] = 'true';
        Environment::clearCache();
        $this->assertTrue(Environment::isDebug());

        // Test with string '1'
        $_ENV['APP_DEBUG'] = '1';
        Environment::clearCache();
        $this->assertTrue(Environment::isDebug());

        // Test with boolean true
        $_ENV['APP_DEBUG'] = true;
        Environment::clearCache();
        $this->assertTrue(Environment::isDebug());

        // Test with string 'false'
        $_ENV['APP_DEBUG'] = 'false';
        Environment::clearCache();
        $this->assertFalse(Environment::isDebug());

        // Test with string '0'
        $_ENV['APP_DEBUG'] = '0';
        Environment::clearCache();
        $this->assertFalse(Environment::isDebug());
    }

    public function testEnvironmentGetWithFallback(): void
    {
        $_ENV['TEST_VAR'] = 'test_value';

        $this->assertEquals('test_value', Environment::get('TEST_VAR'));
        $this->assertEquals('default', Environment::get('NON_EXISTENT', 'default'));
        $this->assertNull(Environment::get('NON_EXISTENT'));
    }

    public function testEnvironmentHas(): void
    {
        $_ENV['TEST_VAR'] = 'test_value';

        $this->assertTrue(Environment::has('TEST_VAR'));
        $this->assertFalse(Environment::has('NON_EXISTENT'));
    }

    public function testIsCli(): void
    {
        // In PHPUnit tests, we're running in CLI mode
        $this->assertTrue(Environment::isCli());
        $this->assertFalse(Environment::isWeb());
    }

    public function testCaching(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['APP_DEBUG'] = 'true';

        // First call should set cache
        $result1 = Environment::isDevelopment();
        $this->assertTrue($result1);

        // Change environment variable but cache should still return same value
        $_ENV['APP_ENV'] = 'production';
        $_ENV['APP_DEBUG'] = 'false';
        $result2 = Environment::isDevelopment();
        $this->assertTrue($result2); // Still true due to cache

        // Clear cache and check again - also disable display_errors
        $originalDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');

        Environment::clearCache();

        try {
            $result3 = Environment::isDevelopment();
            $this->assertFalse($result3); // Now false because cache was cleared
        } finally {
            ini_set('display_errors', $originalDisplayErrors);
        }
    }

    public function testGetDebugInfo(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['APP_DEBUG'] = 'true';
        Environment::clearCache();

        $debugInfo = Environment::getDebugInfo();

        $this->assertIsArray($debugInfo);
        $this->assertArrayHasKey('environment', $debugInfo);
        $this->assertArrayHasKey('is_development', $debugInfo);
        $this->assertArrayHasKey('is_debug', $debugInfo);
        $this->assertArrayHasKey('is_production', $debugInfo);
        $this->assertArrayHasKey('is_testing', $debugInfo);
        $this->assertArrayHasKey('is_cli', $debugInfo);
        $this->assertArrayHasKey('is_web', $debugInfo);

        $this->assertEquals('development', $debugInfo['environment']);
        $this->assertTrue($debugInfo['is_development']);
        $this->assertTrue($debugInfo['is_debug']);
        $this->assertFalse($debugInfo['is_production']);
        $this->assertFalse($debugInfo['is_testing']);
        $this->assertTrue($debugInfo['is_cli']);
        $this->assertFalse($debugInfo['is_web']);
    }

    private array $originalEnv;
    private array $originalServer;
}
