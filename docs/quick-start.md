# Quick Start Guide

Get up and running with PivotPHP Core v1.1.4 in under 5 minutes! This guide will walk you through installation, basic setup, and creating your first API endpoints.

## 🚀 Installation

### Prerequisites
- **PHP 8.1+** with extensions: `json`, `mbstring`
- **Composer** for dependency management

### Install via Composer

```bash
composer require pivotphp/core
```

## 🔥 Your First API

Create a new file `index.php`:

```php
<?php
require_once 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

// Create application instance
$app = new Application();

// Basic route
$app->get('/', function($req, $res) {
    return $res->json(['message' => 'Hello, PivotPHP!']);
});

// API endpoint with parameters
$app->get('/users/:id', function($req, $res) {
    $userId = $req->param('id');
    return $res->json(['user_id' => $userId, 'name' => 'John Doe']);
});

// JSON POST endpoint
$app->post('/users', function($req, $res) {
    $userData = $req->getBody();
    return $res->status(201)->json([
        'message' => 'User created',
        'data' => $userData
    ]);
});

// Run the application
$app->run();
```

### Test Your API

```bash
# Start PHP development server
php -S localhost:8080

# Test endpoints
curl http://localhost:8080/                    # {"message":"Hello, PivotPHP!"}
curl http://localhost:8080/users/123           # {"user_id":"123","name":"John Doe"}
curl -X POST -H "Content-Type: application/json" \
     -d '{"name":"Alice"}' \
     http://localhost:8080/users               # {"message":"User created","data":{"name":"Alice"}}
```

## 🎯 v1.1.3 New Features

### Array Callable Routes (NEW!)

Use array callables with PHP 8.4+ compatibility:

```php
class UserController 
{
    public function index($req, $res) 
    {
        return $res->json(['users' => User::all()]);
    }
    
    public function show($req, $res) 
    {
        $id = $req->param('id');
        return $res->json(['user' => User::find($id)]);
    }
}

// Register routes with array callable syntax
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/:id', [UserController::class, 'show']);
```

### Automatic Performance Optimization

Object pooling and JSON optimization work automatically:

```php
// Large JSON responses are automatically optimized
$app->get('/api/data', function($req, $res) {
    $largeDataset = Database::getAllRecords(); // 1000+ records
    return $res->json($largeDataset); // Automatically uses buffer pooling!
});
```

## 🛡️ Adding Security

Add essential security middleware:

```php
use PivotPHP\Core\Middleware\Security\{CsrfMiddleware, SecurityHeadersMiddleware};
use PivotPHP\Core\Middleware\Http\CorsMiddleware;

// Security middleware
$app->use(new SecurityHeadersMiddleware());
$app->use(new CorsMiddleware([
    'allowed_origins' => ['https://yourfrontend.com'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization']
]));

// CSRF protection for forms
$app->use(new CsrfMiddleware([
    'exclude_paths' => ['/api/*'] // Exclude API routes
]));
```

## 🔍 Route Patterns

PivotPHP supports powerful routing patterns:

```php
// Basic parameters
$app->get('/users/:id', $handler);

// Regex constraints  
$app->get('/users/:id<\\d+>', $handler);           // Only numeric IDs
$app->get('/posts/:slug<[a-z0-9-]+>', $handler);   // Slug format

// Predefined patterns
$app->get('/posts/:date<date>', $handler);          // YYYY-MM-DD format
$app->get('/files/:uuid<uuid>', $handler);          // UUID format

// Multiple parameters
$app->get('/users/:userId/posts/:postId<\\d+>', $handler);
```

## 🔧 Configuration

Create `config/app.php` for application settings:

```php
return [
    'debug' => false,
    'timezone' => 'UTC',
    'cache' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../storage/cache'
    ],
    'cors' => [
        'enabled' => true,
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'max_age' => 86400
    ]
];
```

Load configuration in your application:

```php
use PivotPHP\Core\Core\Config;

$config = new Config(__DIR__ . '/config');
$app = new Application($config);
```

## 📊 Performance Monitoring

Enable performance monitoring for production:

```php
use PivotPHP\Core\Performance\HighPerformanceMode;

// Enable high performance mode
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Get performance metrics
$app->get('/metrics', function($req, $res) {
    $monitor = HighPerformanceMode::getMonitor();
    $metrics = $monitor->getPerformanceMetrics();
    return $res->json($metrics);
});
```

## 🧪 Testing Your API

Create `tests/BasicTest.php`:

```php
<?php
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

class BasicTest extends TestCase
{
    private Application $app;
    
    protected function setUp(): void
    {
        $this->app = new Application();
        $this->app->get('/test', function($req, $res) {
            return $res->json(['status' => 'ok']);
        });
    }
    
    public function testBasicRoute(): void
    {
        // Test implementation here
        $this->assertTrue(true); // Placeholder
    }
}
```

## 🚀 Next Steps

Now that you have a basic API running, explore more features:

1. **[API Reference](API_REFERENCE.md)** - Complete method documentation
2. **[Middleware Guide](reference/middleware.md)** - Security and performance middleware
3. **[Authentication](technical/authentication/README.md)** - JWT and API key authentication  
4. **[Performance Guide](guides/performance.md)** - Optimization strategies
5. **[Examples](reference/examples.md)** - Real-world application examples

## 🏗️ Production Deployment

For production deployment:

```php
// Disable debug mode
$app = new Application(['debug' => false]);

// Enable high performance mode
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Add production middleware
$app->use(new SecurityHeadersMiddleware());
$app->use(new RateLimitMiddleware(['max_requests' => 100, 'window' => 60]));

// Error handling
$app->use(new ErrorMiddleware(['log_errors' => true]));
```

## 🆘 Getting Help

- **[Documentation](README.md)** - Complete documentation
- **[Discord Community](https://discord.gg/DMtxsP7z)** - Real-time support
- **[GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)** - Bug reports and feature requests
- **[Examples Repository](examples/)** - Practical examples

---

**Congratulations!** You now have a solid foundation for building high-performance APIs with PivotPHP Core v1.1.3. 🎉