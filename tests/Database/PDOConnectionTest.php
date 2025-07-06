<?php

declare(strict_types=1);

namespace Express\Tests\Database;

use PHPUnit\Framework\TestCase;
use Express\Database\PDOConnection;
use Express\Exceptions\DatabaseException;
use PDO;

class PDOConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset singleton instance
        PDOConnection::close();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up
        PDOConnection::close();
    }

    public function testConfigureWithMySQLDriver(): void
    {
        $config = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'root',
            'password' => ''
        ];

        PDOConnection::configure($config);

        // Test that MySQL-specific options are set
        $reflection = new \ReflectionClass(PDOConnection::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $actualConfig = $configProperty->getValue();

        if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            $this->assertArrayHasKey(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $actualConfig['options']);
            $this->assertTrue($actualConfig['options'][PDO::MYSQL_ATTR_USE_BUFFERED_QUERY]);

            // Test that collation is set via INIT command
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $this->assertArrayHasKey(PDO::MYSQL_ATTR_INIT_COMMAND, $actualConfig['options']);
                $this->assertEquals(
                    'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                    $actualConfig['options'][PDO::MYSQL_ATTR_INIT_COMMAND]
                );
            }
        } else {
            $this->markTestSkipped('MySQL PDO extension not available');
        }
    }

    public function testConfigureWithPostgreSQLDriver(): void
    {
        $config = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'postgres',
            'password' => ''
        ];

        PDOConnection::configure($config);

        // Test that MySQL-specific options are NOT set
        $reflection = new \ReflectionClass(PDOConnection::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $actualConfig = $configProperty->getValue();

        if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            $this->assertArrayNotHasKey(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $actualConfig['options']);
        } else {
            // Assert that the configuration was successful
            $this->assertIsArray($actualConfig['options']);
        }
    }

    public function testConfigureWithSQLiteDriver(): void
    {
        $config = [
            'driver' => 'sqlite',
            'database' => ':memory:'
        ];

        PDOConnection::configure($config);

        // Test that MySQL-specific options are NOT set
        $reflection = new \ReflectionClass(PDOConnection::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $actualConfig = $configProperty->getValue();

        if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            $this->assertArrayNotHasKey(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $actualConfig['options']);
        } else {
            // Assert that the configuration was successful
            $this->assertIsArray($actualConfig['options']);
        }
    }

    public function testCommonOptionsAreAlwaysSet(): void
    {
        $drivers = ['mysql', 'pgsql', 'sqlite'];

        foreach ($drivers as $driver) {
            PDOConnection::close();

            $config = [
                'driver' => $driver,
                'database' => $driver === 'sqlite' ? ':memory:' : 'test'
            ];

            PDOConnection::configure($config);

            $reflection = new \ReflectionClass(PDOConnection::class);
            $configProperty = $reflection->getProperty('config');
            $configProperty->setAccessible(true);
            $actualConfig = $configProperty->getValue();

            // Common options should always be present
            $this->assertArrayHasKey(PDO::ATTR_ERRMODE, $actualConfig['options']);
            $this->assertEquals(PDO::ERRMODE_EXCEPTION, $actualConfig['options'][PDO::ATTR_ERRMODE]);

            $this->assertArrayHasKey(PDO::ATTR_DEFAULT_FETCH_MODE, $actualConfig['options']);
            $this->assertEquals(PDO::FETCH_ASSOC, $actualConfig['options'][PDO::ATTR_DEFAULT_FETCH_MODE]);

            $this->assertArrayHasKey(PDO::ATTR_EMULATE_PREPARES, $actualConfig['options']);
            $this->assertFalse($actualConfig['options'][PDO::ATTR_EMULATE_PREPARES]);
        }
    }

    public function testUserProvidedOptionsOverrideDefaults(): void
    {
        $config = [
            'driver' => 'mysql',
            'database' => 'test',
            'options' => [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
                PDO::ATTR_PERSISTENT => true
            ]
        ];

        PDOConnection::configure($config);

        $reflection = new \ReflectionClass(PDOConnection::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $actualConfig = $configProperty->getValue();

        // User option should override default
        $this->assertEquals(PDO::FETCH_NUM, $actualConfig['options'][PDO::ATTR_DEFAULT_FETCH_MODE]);

        // Additional user option should be included
        $this->assertTrue($actualConfig['options'][PDO::ATTR_PERSISTENT]);

        // Other defaults should still be present
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $actualConfig['options'][PDO::ATTR_ERRMODE]);
    }

    public function testSQLiteConnectionWorks(): void
    {
        $config = [
            'driver' => 'sqlite',
            'database' => ':memory:'
        ];

        PDOConnection::configure($config);

        // This should work without throwing an exception
        $pdo = PDOConnection::getInstance();

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertEquals('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function testInvalidConnectionThrowsException(): void
    {
        $config = [
            'driver' => 'mysql',
            'host' => 'invalid_host_that_does_not_exist',
            'database' => 'test',
            'username' => 'invalid',
            'password' => 'invalid'
        ];

        PDOConnection::configure($config);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database connection failed');

        PDOConnection::getInstance();
    }

    public function testCloseConnectionClearsConfiguration(): void
    {
        // Configure connection
        $config = [
            'driver' => 'sqlite',
            'database' => ':memory:'
        ];

        PDOConnection::configure($config);

        // Get instance to ensure connection is established
        $pdo = PDOConnection::getInstance();
        $this->assertInstanceOf(PDO::class, $pdo);

        // Close connection
        PDOConnection::close();

        // Try to get instance again - should use default config from environment
        // Since we don't have environment variables set, this should create a connection
        // with default values
        $reflection = new \ReflectionClass(PDOConnection::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);

        // Config should be empty after close
        $this->assertEmpty($configProperty->getValue());

        // Configure again with different settings to ensure clean state
        $newConfig = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
            ]
        ];

        PDOConnection::configure($newConfig);
        $actualConfig = $configProperty->getValue();

        // Should have new config, not old one
        $this->assertEquals('sqlite', $actualConfig['driver']);
        $this->assertArrayHasKey(PDO::ATTR_ERRMODE, $actualConfig['options']);
    }

    public function testMySQLCollationIsSetViaInitCommand(): void
    {
        if (!defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $this->markTestSkipped('MySQL PDO extension not available');
        }

        $config = [
            'driver' => 'mysql',
            'database' => 'test',
            'charset' => 'latin1',
            'collation' => 'latin1_german1_ci'
        ];

        PDOConnection::configure($config);

        $reflection = new \ReflectionClass(PDOConnection::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $actualConfig = $configProperty->getValue();

        // Should have INIT command with custom charset and collation
        $this->assertArrayHasKey(PDO::MYSQL_ATTR_INIT_COMMAND, $actualConfig['options']);
        $this->assertEquals(
            'SET NAMES latin1 COLLATE latin1_german1_ci',
            $actualConfig['options'][PDO::MYSQL_ATTR_INIT_COMMAND]
        );
    }

    public function testNoCollationMeansNoInitCommand(): void
    {
        $config = [
            'driver' => 'mysql',
            'database' => 'test',
            'charset' => 'utf8mb4',
            'collation' => '' // Empty collation
        ];

        PDOConnection::configure($config);

        $reflection = new \ReflectionClass(PDOConnection::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $actualConfig = $configProperty->getValue();

        // Should not have INIT command if collation is empty
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $this->assertArrayNotHasKey(PDO::MYSQL_ATTR_INIT_COMMAND, $actualConfig['options']);
        } else {
            // Assert that config was at least processed correctly
            $this->assertEquals('mysql', $actualConfig['driver']);
        }
    }
}
