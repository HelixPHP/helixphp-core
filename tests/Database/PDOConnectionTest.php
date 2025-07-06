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
}
