<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Database\PDOConnection;

/**
 * Multi-Database Benchmark for PivotPHP
 *
 * Compares performance across MySQL, PostgreSQL, and MariaDB
 */
class MultiDatabaseBenchmark
{
    private array $databases = [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'mysql',
            'port' => 3306,
            'database' => 'express_benchmark',
            'username' => 'benchmark_user',
            'password' => 'benchmark_pass',
            'charset' => 'utf8mb4'
        ],
        'postgres' => [
            'driver' => 'pgsql',
            'host' => 'postgres',
            'port' => 5432,
            'database' => 'express_benchmark',
            'username' => 'benchmark_user',
            'password' => 'benchmark_pass',
            'charset' => 'utf8'
        ],
        'mariadb' => [
            'driver' => 'mysql', // MariaDB uses mysql driver
            'host' => 'mariadb',
            'port' => 3306,
            'database' => 'express_benchmark',
            'username' => 'benchmark_user',
            'password' => 'benchmark_pass',
            'charset' => 'utf8mb4'
        ]
    ];

    private array $results = [];
    private int $iterations = 1000;
    private int $warmup = 100;

    /**
     * Run benchmarks for all databases
     */
    public function run(): void
    {
        echo "🚀 PivotPHP Multi-Database Benchmark\n";
        echo "======================================\n\n";
        echo "Iterations per test: {$this->iterations}\n";
        echo "Warmup iterations: {$this->warmup}\n\n";

        // Check if running in Docker
        $isDocker = getenv('BENCHMARK_ENV') === 'docker';
        if (!$isDocker) {
            echo "⚠️  Running locally. For best results, use Docker:\n";
            echo "   docker-compose -f docker-compose.benchmark.yml up\n\n";

            // Adjust database hosts for local environment
            foreach ($this->databases as &$config) {
                $config['host'] = 'localhost';
            }
        }

        // Test each database
        foreach ($this->databases as $name => $config) {
            echo "📊 Testing $name...\n";
            echo str_repeat('-', 40) . "\n";

            try {
                PDOConnection::close();
                PDOConnection::configure($config);

                // Verify connection
                $stats = PDOConnection::getStats();
                echo "Connected to: {$stats['driver']} {$stats['server_version']}\n";

                // Run benchmarks
                $this->results[$name] = $this->runBenchmarks($name);

                echo "\n";

            } catch (\Exception $e) {
                echo "❌ Failed to connect to $name: " . $e->getMessage() . "\n\n";
                $this->results[$name] = ['error' => $e->getMessage()];
            }
        }

        // Display comparison
        $this->displayComparison();

        // Save results
        $this->saveResults();
    }

    /**
     * Run benchmark suite for a specific database
     */
    private function runBenchmarks(string $dbName): array
    {
        $results = [];

        // Warmup
        echo "Warming up...\n";
        for ($i = 0; $i < $this->warmup; $i++) {
            $this->simpleSelect(rand(1, 100));
        }

        // Benchmark operations
        $operations = [
            'simple_select' => 'Simple SELECT by ID',
            'bulk_insert' => 'Bulk INSERT (10 rows)',
            'complex_join' => 'Complex JOIN query',
            'aggregation' => 'Aggregation query',
            'update_transaction' => 'UPDATE with transaction',
            'index_scan' => 'Index scan query'
        ];

        foreach ($operations as $method => $description) {
            echo "  Testing $description... ";
            $results[$method] = $this->$method();
            echo sprintf("%.2f ops/sec\n", $results[$method]['ops_per_sec']);
        }

        return $results;
    }

    /**
     * Simple SELECT benchmark
     */
    private function simple_select(): array
    {
        $times = [];

        for ($i = 0; $i < $this->iterations; $i++) {
            $id = rand(1, 1000);
            $start = microtime(true);

            PDOConnection::query("SELECT * FROM users WHERE id = ?", [$id]);

            $times[] = microtime(true) - $start;
        }

        return $this->calculateStats($times);
    }

    /**
     * Bulk INSERT benchmark
     */
    private function bulk_insert(): array
    {
        $times = [];

        for ($i = 0; $i < $this->iterations; $i++) {
            $start = microtime(true);

            PDOConnection::beginTransaction();

            for ($j = 0; $j < 10; $j++) {
                PDOConnection::execute(
                    "INSERT INTO users (name, email, password) VALUES (?, ?, ?)",
                    [
                        "Bench User $i-$j",
                        "bench$i-$j@test.com",
                        password_hash("test", PASSWORD_DEFAULT)
                    ]
                );
            }

            PDOConnection::commit();

            $times[] = microtime(true) - $start;
        }

        return $this->calculateStats($times);
    }

    /**
     * Complex JOIN benchmark
     */
    private function complex_join(): array
    {
        $times = [];

        for ($i = 0; $i < $this->iterations; $i++) {
            $start = microtime(true);

            PDOConnection::query("
                SELECT u.*, COUNT(p.id) as post_count, MAX(p.created_at) as last_post
                FROM users u
                LEFT JOIN posts p ON u.id = p.user_id
                WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY u.id
                ORDER BY post_count DESC
                LIMIT 10
            ");

            $times[] = microtime(true) - $start;
        }

        return $this->calculateStats($times);
    }

    /**
     * Aggregation benchmark
     */
    private function aggregation(): array
    {
        $times = [];

        for ($i = 0; $i < $this->iterations; $i++) {
            $start = microtime(true);

            PDOConnection::query("
                SELECT
                    status,
                    COUNT(*) as count,
                    AVG(views) as avg_views,
                    MAX(views) as max_views
                FROM posts
                GROUP BY status
            ");

            $times[] = microtime(true) - $start;
        }

        return $this->calculateStats($times);
    }

    /**
     * UPDATE with transaction benchmark
     */
    private function update_transaction(): array
    {
        $times = [];

        for ($i = 0; $i < $this->iterations; $i++) {
            $id = rand(1, 1000);
            $start = microtime(true);

            PDOConnection::beginTransaction();

            PDOConnection::execute(
                "UPDATE users SET name = ? WHERE id = ?",
                ["Updated User $i", $id]
            );

            PDOConnection::execute(
                "UPDATE posts SET views = views + 1 WHERE user_id = ?",
                [$id]
            );

            PDOConnection::commit();

            $times[] = microtime(true) - $start;
        }

        return $this->calculateStats($times);
    }

    /**
     * Index scan benchmark
     */
    private function index_scan(): array
    {
        $times = [];
        $emails = ['test%', 'user%', 'bench%', 'admin%'];

        for ($i = 0; $i < $this->iterations; $i++) {
            $pattern = $emails[array_rand($emails)];
            $start = microtime(true);

            PDOConnection::query(
                "SELECT * FROM users WHERE email LIKE ? ORDER BY created_at DESC LIMIT 50",
                [$pattern]
            );

            $times[] = microtime(true) - $start;
        }

        return $this->calculateStats($times);
    }

    /**
     * Simple SELECT by ID (for warmup)
     */
    private function simpleSelect(int $id): void
    {
        PDOConnection::query("SELECT * FROM users WHERE id = ?", [$id]);
    }

    /**
     * Calculate statistics
     */
    private function calculateStats(array $times): array
    {
        sort($times);
        $count = count($times);

        return [
            'iterations' => $count,
            'total_time' => array_sum($times),
            'average' => array_sum($times) / $count,
            'median' => $times[intval($count / 2)],
            'min' => min($times),
            'max' => max($times),
            'p95' => $times[intval($count * 0.95)],
            'p99' => $times[intval($count * 0.99)],
            'ops_per_sec' => $count / array_sum($times)
        ];
    }

    /**
     * Display comparison results
     */
    private function displayComparison(): void
    {
        echo "\n📊 Database Performance Comparison\n";
        echo "==================================\n\n";

        // Collect all operations
        $operations = [];
        foreach ($this->results as $db => $results) {
            if (!isset($results['error'])) {
                foreach ($results as $op => $stats) {
                    $operations[$op] = true;
                }
            }
        }

        // Display comparison table
        foreach (array_keys($operations) as $operation) {
            echo sprintf("📌 %s\n", str_replace('_', ' ', ucfirst($operation)));

            $fastest = null;
            $fastestOps = 0;

            foreach ($this->results as $db => $results) {
                if (isset($results[$operation])) {
                    $ops = $results[$operation]['ops_per_sec'];
                    echo sprintf("   %s: %.2f ops/sec (%.4f ms avg)\n",
                        str_pad($db, 10),
                        $ops,
                        $results[$operation]['average'] * 1000
                    );

                    if ($ops > $fastestOps) {
                        $fastest = $db;
                        $fastestOps = $ops;
                    }
                }
            }

            if ($fastest) {
                echo sprintf("   🏆 Fastest: %s\n", $fastest);
            }
            echo "\n";
        }
    }

    /**
     * Save results to file
     */
    private function saveResults(): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = __DIR__ . "/results/multi_database_benchmark_$timestamp.json";

        $data = [
            'timestamp' => $timestamp,
            'environment' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'is_docker' => getenv('BENCHMARK_ENV') === 'docker',
                'iterations' => $this->iterations,
                'warmup' => $this->warmup
            ],
            'databases' => $this->results
        ];

        if (!is_dir(__DIR__ . '/results')) {
            mkdir(__DIR__ . '/results', 0755, true);
        }

        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        echo "\n💾 Results saved to: $filename\n";
    }
}

// Run benchmark
try {
    $benchmark = new MultiDatabaseBenchmark();
    $benchmark->run();
} catch (\Exception $e) {
    echo "❌ Benchmark failed: " . $e->getMessage() . "\n";
    exit(1);
}
