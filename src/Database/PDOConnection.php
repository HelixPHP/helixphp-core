<?php

declare(strict_types=1);

namespace Express\Database;

use PDO;
use PDOException;
use Express\Exceptions\DatabaseException;

/**
 * PDO Database Connection Manager
 *
 * Provides a simple interface for database operations using PDO
 * Designed for benchmark testing with real database interactions
 */
class PDOConnection
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Configure database connection
     */
    public static function configure(array $config): void
    {
        // Default options common to all drivers
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Merge with provided config
        $config = array_merge(
            [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'test',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => []
            ],
            $config
        );

        // Apply driver-specific options
        if (in_array($config['driver'], ['mysql', 'mariadb']) && defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            $defaultOptions[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
        }

        // Merge default options with user-provided options (preserving numeric keys)
        $config['options'] = $config['options'] + $defaultOptions;

        self::$config = $config;
    }

    /**
     * Get PDO instance (singleton)
     *
     * @throws DatabaseException If connection fails
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }

        if (self::$instance === null) {
            throw new DatabaseException('Failed to establish database connection');
        }

        return self::$instance;
    }

    /**
     * Connect to database
     */
    private static function connect(): void
    {
        $config = self::$config;

        if (empty($config)) {
            // Load from environment if not configured
            $config = [
                'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => (int)($_ENV['DB_PORT'] ?? 3306),
                'database' => $_ENV['DB_DATABASE'] ?? 'test',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ];
            self::configure($config);
        }

        try {
            // Build DSN based on driver
            switch ($config['driver']) {
                case 'pgsql':
                case 'postgres':
                    $dsn = sprintf(
                        'pgsql:host=%s;port=%d;dbname=%s',
                        $config['host'],
                        $config['port'],
                        $config['database']
                    );
                    break;

                case 'sqlite':
                    $dsn = sprintf('sqlite:%s', $config['database']);
                    break;

                case 'mysql':
                case 'mariadb':
                default:
                    $dsn = sprintf(
                        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                        $config['host'],
                        $config['port'],
                        $config['database'],
                        $config['charset']
                    );
                    break;
            }

            self::$instance = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            // Set driver-specific attributes
            if (in_array($config['driver'], ['mysql', 'mariadb']) && defined('PDO::MYSQL_ATTR_COMPRESS')) {
                self::$instance->setAttribute(PDO::MYSQL_ATTR_COMPRESS, true);
            }

            self::$instance->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        } catch (PDOException $e) {
            throw new DatabaseException(
                'Database connection failed: ' . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Execute a query and return results
     */
    public static function query(string $sql, array $params = []): array
    {
        $pdo = self::getInstance();

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException(
                'Query execution failed: ' . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Execute a statement (INSERT, UPDATE, DELETE)
     */
    public static function execute(string $sql, array $params = []): int
    {
        $pdo = self::getInstance();

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException(
                'Statement execution failed: ' . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Get last insert ID
     *
     * @throws DatabaseException If unable to get last insert ID
     */
    public static function lastInsertId(): string
    {
        $lastId = self::getInstance()->lastInsertId();

        if ($lastId === false) {
            throw new DatabaseException('Unable to retrieve last insert ID');
        }

        return $lastId;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Close connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }

    /**
     * Get connection statistics for benchmarking
     */
    public static function getStats(): array
    {
        $pdo = self::getInstance();

        return [
            'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
            'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'client_version' => $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION),
            'connection_status' => $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS),
            'server_info' => $pdo->getAttribute(PDO::ATTR_SERVER_INFO),
        ];
    }
}
