<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Database\PDOConnection;
use Helix\Http\Request;
use Helix\Http\Response;

/**
 * Database Benchmark for HelixPHP
 * 
 * Tests performance with real database operations
 * Simulates production-like scenarios
 */
class DatabaseBenchmark
{
    private Application $app;
    private array $results = [];
    private int $iterations = 1000;
    private int $warmup = 100;
    
    public function __construct()
    {
        $this->app = new Application();
        $this->setupDatabase();
        $this->setupRoutes();
    }
    
    /**
     * Setup database tables and seed data
     */
    private function setupDatabase(): void
    {
        // Create users table
        PDOConnection::execute("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create posts table
        PDOConnection::execute("
            CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                views INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at),
                FULLTEXT idx_fulltext (title, content)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Seed initial data if empty
        $userCount = PDOConnection::query("SELECT COUNT(*) as count FROM users")[0]['count'];
        
        if ($userCount < 1000) {
            echo "Seeding database with test data...\n";
            
            // Insert users
            for ($i = 0; $i < 1000; $i++) {
                PDOConnection::execute(
                    "INSERT INTO users (name, email, password) VALUES (?, ?, ?)",
                    [
                        "User $i",
                        "user$i@example.com",
                        password_hash("password$i", PASSWORD_DEFAULT)
                    ]
                );
            }
            
            // Insert posts
            for ($i = 0; $i < 5000; $i++) {
                PDOConnection::execute(
                    "INSERT INTO posts (user_id, title, content, status, views) VALUES (?, ?, ?, ?, ?)",
                    [
                        rand(1, 1000),
                        "Post Title $i - " . bin2hex(random_bytes(8)),
                        "This is the content of post $i. " . str_repeat("Lorem ipsum dolor sit amet. ", rand(10, 50)),
                        ['draft', 'published', 'archived'][rand(0, 2)],
                        rand(0, 10000)
                    ]
                );
            }
            
            echo "Database seeding completed.\n";
        }
    }
    
    /**
     * Setup HelixPHP routes for benchmarking
     */
    private function setupRoutes(): void
    {
        // Simple SELECT query
        $this->app->get('/api/users/:id', function (Request $req, Response $res) {
            $id = $req->param('id');
            $user = PDOConnection::query("SELECT * FROM users WHERE id = ?", [$id]);
            
            if (empty($user)) {
                return $res->status(404)->json(['error' => 'User not found']);
            }
            
            return $res->json($user[0]);
        });
        
        // Complex JOIN query
        $this->app->get('/api/users/:id/posts', function (Request $req, Response $res) {
            $id = $req->param('id');
            $posts = PDOConnection::query("
                SELECT p.*, u.name as author_name, u.email as author_email
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id = ? AND p.status = 'published'
                ORDER BY p.created_at DESC
                LIMIT 10
            ", [$id]);
            
            return $res->json(['posts' => $posts]);
        });
        
        // Search with full-text
        $this->app->get('/api/posts/search', function (Request $req, Response $res) {
            $query = $req->query('q', '');
            
            if (strlen($query) < 3) {
                return $res->json(['posts' => []]);
            }
            
            $posts = PDOConnection::query("
                SELECT p.*, u.name as author_name,
                       MATCH(p.title, p.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE MATCH(p.title, p.content) AGAINST(? IN NATURAL LANGUAGE MODE)
                AND p.status = 'published'
                ORDER BY relevance DESC
                LIMIT 20
            ", [$query, $query]);
            
            return $res->json(['posts' => $posts]);
        });
        
        // Aggregation query
        $this->app->get('/api/stats/posts', function (Request $req, Response $res) {
            $stats = PDOConnection::query("
                SELECT 
                    COUNT(*) as total_posts,
                    SUM(views) as total_views,
                    AVG(views) as avg_views,
                    MAX(views) as max_views,
                    status,
                    DATE(created_at) as date
                FROM posts
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY status, DATE(created_at)
                ORDER BY date DESC
            ");
            
            return $res->json(['stats' => $stats]);
        });
        
        // Insert operation
        $this->app->post('/api/posts', function (Request $req, Response $res) {
            $data = $req->getParsedBody();
            
            PDOConnection::execute(
                "INSERT INTO posts (user_id, title, content, status) VALUES (?, ?, ?, ?)",
                [
                    $data['user_id'] ?? 1,
                    $data['title'] ?? 'Test Post',
                    $data['content'] ?? 'Test content',
                    $data['status'] ?? 'draft'
                ]
            );
            
            $id = PDOConnection::lastInsertId();
            
            return $res->status(201)->json(['id' => $id, 'message' => 'Post created']);
        });
        
        // Update with transaction
        $this->app->put('/api/posts/:id', function (Request $req, Response $res) {
            $id = $req->param('id');
            $data = $req->getParsedBody();
            
            PDOConnection::beginTransaction();
            
            try {
                // Update post
                $affected = PDOConnection::execute(
                    "UPDATE posts SET title = ?, content = ?, status = ? WHERE id = ?",
                    [
                        $data['title'] ?? 'Updated Title',
                        $data['content'] ?? 'Updated content',
                        $data['status'] ?? 'published',
                        $id
                    ]
                );
                
                // Update view count
                PDOConnection::execute(
                    "UPDATE posts SET views = views + 1 WHERE id = ?",
                    [$id]
                );
                
                PDOConnection::commit();
                
                return $res->json(['affected' => $affected, 'message' => 'Post updated']);
                
            } catch (\Exception $e) {
                PDOConnection::rollback();
                return $res->status(500)->json(['error' => 'Update failed']);
            }
        });
    }
    
    /**
     * Run benchmarks
     */
    public function run(): void
    {
        echo "🚀 HelixPHP Database Benchmark\n";
        echo "================================\n\n";
        
        // Warmup
        echo "Warming up...\n";
        $this->warmupBenchmarks();
        
        // Run benchmarks
        $benchmarks = [
            'simple_select' => 'Simple SELECT by ID',
            'join_query' => 'JOIN query with ordering',
            'fulltext_search' => 'Full-text search',
            'aggregation' => 'Aggregation with GROUP BY',
            'insert_operation' => 'INSERT operation',
            'transaction_update' => 'UPDATE with transaction'
        ];
        
        foreach ($benchmarks as $method => $description) {
            echo "Running: $description\n";
            $this->results[$method] = $this->$method();
        }
        
        // Display results
        $this->displayResults();
    }
    
    /**
     * Warmup benchmarks
     */
    private function warmupBenchmarks(): void
    {
        for ($i = 0; $i < $this->warmup; $i++) {
            $this->makeRequest('GET', '/api/users/1');
            $this->makeRequest('GET', '/api/users/1/posts');
        }
    }
    
    /**
     * Benchmark simple SELECT
     */
    private function simple_select(): array
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $userId = rand(1, 1000);
            $start = microtime(true);
            
            $this->makeRequest('GET', "/api/users/$userId");
            
            $times[] = microtime(true) - $start;
        }
        
        return $this->calculateStats($times);
    }
    
    /**
     * Benchmark JOIN query
     */
    private function join_query(): array
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $userId = rand(1, 1000);
            $start = microtime(true);
            
            $this->makeRequest('GET', "/api/users/$userId/posts");
            
            $times[] = microtime(true) - $start;
        }
        
        return $this->calculateStats($times);
    }
    
    /**
     * Benchmark full-text search
     */
    private function fulltext_search(): array
    {
        $times = [];
        $searchTerms = ['lorem', 'ipsum', 'dolor', 'post', 'content', 'title'];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $term = $searchTerms[array_rand($searchTerms)];
            $start = microtime(true);
            
            $this->makeRequest('GET', "/api/posts/search?q=$term");
            
            $times[] = microtime(true) - $start;
        }
        
        return $this->calculateStats($times);
    }
    
    /**
     * Benchmark aggregation
     */
    private function aggregation(): array
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = microtime(true);
            
            $this->makeRequest('GET', '/api/stats/posts');
            
            $times[] = microtime(true) - $start;
        }
        
        return $this->calculateStats($times);
    }
    
    /**
     * Benchmark INSERT operation
     */
    private function insert_operation(): array
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = microtime(true);
            
            $this->makeRequest('POST', '/api/posts', [
                'user_id' => rand(1, 1000),
                'title' => "Benchmark Post $i",
                'content' => "Benchmark content " . bin2hex(random_bytes(16)),
                'status' => 'published'
            ]);
            
            $times[] = microtime(true) - $start;
        }
        
        return $this->calculateStats($times);
    }
    
    /**
     * Benchmark transaction update
     */
    private function transaction_update(): array
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $postId = rand(1, 5000);
            $start = microtime(true);
            
            $this->makeRequest('PUT', "/api/posts/$postId", [
                'title' => "Updated Title $i",
                'content' => "Updated content " . time(),
                'status' => 'published'
            ]);
            
            $times[] = microtime(true) - $start;
        }
        
        return $this->calculateStats($times);
    }
    
    /**
     * Make HTTP request to the application
     */
    private function makeRequest(string $method, string $uri, array $data = []): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['PATH_INFO'] = parse_url($uri, PHP_URL_PATH);
        $_SERVER['QUERY_STRING'] = parse_url($uri, PHP_URL_QUERY) ?? '';
        
        if (!empty($data)) {
            $_POST = $data;
            $_SERVER['CONTENT_TYPE'] = 'application/json';
        }
        
        ob_start();
        $this->app->run();
        ob_end_clean();
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
     * Display results
     */
    private function displayResults(): void
    {
        echo "\n📊 Benchmark Results\n";
        echo "===================\n\n";
        
        foreach ($this->results as $benchmark => $stats) {
            echo sprintf("📌 %s\n", str_replace('_', ' ', ucfirst($benchmark)));
            echo sprintf("   Iterations: %d\n", $stats['iterations']);
            echo sprintf("   Average: %.4f ms\n", $stats['average'] * 1000);
            echo sprintf("   Median: %.4f ms\n", $stats['median'] * 1000);
            echo sprintf("   Min: %.4f ms\n", $stats['min'] * 1000);
            echo sprintf("   Max: %.4f ms\n", $stats['max'] * 1000);
            echo sprintf("   P95: %.4f ms\n", $stats['p95'] * 1000);
            echo sprintf("   P99: %.4f ms\n", $stats['p99'] * 1000);
            echo sprintf("   Ops/sec: %.2f\n", $stats['ops_per_sec']);
            echo "\n";
        }
        
        // Save results
        $this->saveResults();
    }
    
    /**
     * Save results to file
     */
    private function saveResults(): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = __DIR__ . "/results/database_benchmark_$timestamp.json";
        
        $data = [
            'timestamp' => $timestamp,
            'environment' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'database' => PDOConnection::getStats(),
                'iterations' => $this->iterations,
                'warmup' => $this->warmup
            ],
            'results' => $this->results
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
    $benchmark = new DatabaseBenchmark();
    $benchmark->run();
} catch (\Exception $e) {
    echo "❌ Benchmark failed: " . $e->getMessage() . "\n";
    exit(1);
}