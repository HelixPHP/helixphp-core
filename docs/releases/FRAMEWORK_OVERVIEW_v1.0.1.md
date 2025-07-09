# PivotPHP Framework v1.0.1 - Complete Overview

<div align="center">

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.0.1-brightgreen.svg)](https://github.com/PivotPHP/pivotphp-core/releases)
[![PSR](https://img.shields.io/badge/PSR-7%20|%2011%20|%2012%20|%2015-orange.svg)](https://www.php-fig.org/psr/)

**A lightweight, fast, and secure microframework for modern PHP applications**

</div>

## 🎯 What is PivotPHP?

PivotPHP v1.0.1 is a high-performance microframework designed for rapid development of modern PHP applications. This minor release introduces advanced route validation with regex support while maintaining full backward compatibility.

### Key Highlights v1.0.1
- **🆕 Regex Route Validation**: Advanced pattern matching with constraints
- **🔄 PSR-7 Dual Version Support**: Full compatibility with both v1.x and v2.x
  - Automatic version detection
  - Script to switch between versions
  - Enables ReactPHP integration
- **🚀 High Performance**: 13.9M operations/second (278x improvement)
- **🔒 Security First**: Built-in CORS, CSRF, XSS protection
- **📋 PSR Compliant**: Full PSR-7, PSR-11, PSR-12, PSR-15 support
- **🧪 Type Safe**: PHPStan Level 9 analysis
- **⚡ Zero Dependencies**: Core framework with minimal footprint
- **✅ Full Backward Compatibility**: All v1.0.0 code works without changes

## 🚀 Quick Start

### Installation

```bash
composer create-project pivotphp/core my-app
cd my-app
php -S localhost:8000 -t public
```

### Hello World

```php
<?php
// public/index.php
require '../vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

$app->get('/', function ($req, $res) {
    return $res->json(['message' => 'Hello PivotPHP v1.0.1!']);
});

$app->run();
```

## 🆕 New in v1.0.1: Advanced Route Validation

### Regex Constraints for Parameters

```php
// Numeric ID validation
$app->get('/users/:id<\d+>', function ($req, $res) {
    $id = $req->param('id'); // Guaranteed to be numeric
    return $res->json(['user_id' => $id]);
});

// Date format validation
$app->get('/posts/:year<\d{4}>/:month<\d{2}>/:day<\d{2}>', function ($req, $res) {
    return $res->json([
        'date' => sprintf('%s-%s-%s', 
            $req->param('year'),
            $req->param('month'),
            $req->param('day')
        )
    ]);
});

// Using predefined shortcuts
$app->get('/articles/:slug<slug>', handler);  // [a-z0-9-]+
$app->get('/users/:uuid<uuid>', handler);     // UUID format
$app->get('/codes/:code<alnum>', handler);    // Alphanumeric
```

### Full Regex Blocks

```php
// API versioning with regex
$app->get('/api/{^v(\d+)$}/users', function ($req, $res) {
    // Matches: /api/v1/users, /api/v2/users
    // Version number is captured automatically
});

// File extensions validation
$app->get('/download/{^(.+)\.(pdf|doc|txt)$}', function ($req, $res) {
    // Matches: /download/report.pdf, /download/notes.txt
    // Filename and extension captured separately
});
```

### Available Shortcuts

- `int` - Integers (`\d+`)
- `slug` - URL-friendly slugs (`[a-z0-9-]+`)
- `alpha` - Letters only (`[a-zA-Z]+`)
- `alnum` - Alphanumeric (`[a-zA-Z0-9]+`)
- `uuid` - UUID format
- `date` - YYYY-MM-DD format
- `year`, `month`, `day` - Date components

### Backward Compatibility

All existing route patterns continue to work:

```php
// Traditional parameters (still supported)
$app->get('/users/:id', handler);
$app->get('/posts/:category/:slug', handler);

// New regex constraints (opt-in feature)
$app->get('/users/:id<\d+>', handler);
$app->get('/posts/:category<alpha>/:slug<slug>', handler);
```

## 🏗️ Architecture

### Core Components

#### Application Core
- **Application**: Main application container and router
- **Container**: PSR-11 dependency injection container
- **Config**: Configuration management system

#### HTTP Layer
- **Request/Response**: PSR-7 HTTP message implementations
- **Middleware**: PSR-15 middleware pipeline
- **Routing**: Fast route matching and parameter extraction

#### Security
- **CORS Middleware**: Cross-origin resource sharing
- **CSRF Protection**: Cross-site request forgery prevention
- **XSS Protection**: Cross-site scripting mitigation
- **Security Headers**: Comprehensive security headers

## 📊 Performance

### Benchmark Results v1.0.1

| Operation | Ops/Second | Memory Usage | Latency (p99) |
|-----------|------------|--------------|---------------|
| Route Matching | 13.9M | 89MB peak | 0.072μs |
| JSON Response | 11M | 45MB | 0.091μs |
| CORS Headers | 52M | 23MB | 0.019μs |
| Middleware Pipeline | 2.2M | 67MB | 0.455μs |
| Static Routes | 15.2M | 12MB | 0.066μs |
| Dynamic Routes | 8.7M | 34MB | 0.115μs |
| Auth Validation | 4.1M | 56MB | 0.244μs |

### Performance Improvements v1.0.1
- **278x faster** route matching compared to v0.1.0
- **95% less memory** usage for static routes
- **Zero allocation** for common operations
- **Sub-microsecond** response times

### Advanced Optimizations
- **Memory Mapping**: Zero-copy operations
- **Route Caching**: Compiled route patterns with non-greedy regex
- **Middleware Compilation**: Pre-compiled pipeline
- **JIT Compilation**: PHP 8.4 JIT optimized
- **Type Inference**: Full type safety with PHPStan Level 9

## 🔧 Core Features

### 1. Routing System

```php
// Basic routing
$app->get('/users', 'UserController@index');
$app->post('/users', 'UserController@create');
$app->put('/users/{id}', 'UserController@update');
$app->delete('/users/{id}', 'UserController@delete');

// Route groups
$app->group('/api/v1', function ($group) {
    $group->get('/users', 'UserController@index');
    $group->post('/users', 'UserController@create');
});

// Middleware on routes
$app->get('/admin', 'AdminController@dashboard')
    ->middleware(AuthMiddleware::class);
```

### 2. Middleware System

```php
// Global middleware
$app->use(new CorsMiddleware());
$app->use(new SecurityHeadersMiddleware());

// Route-specific middleware
$app->post('/login', 'AuthController@login')
    ->middleware(CsrfMiddleware::class);

// Custom middleware
class CustomMiddleware extends AbstractMiddleware
{
    public function process($request, $handler)
    {
        // Pre-processing
        $response = $handler->handle($request);
        // Post-processing
        return $response;
    }
}
```

### 3. Dependency Injection

```php
// Service registration
$app->bind('database', function () {
    return new Database($_ENV['DB_DSN']);
});

// Service resolution
$app->get('/users', function ($req, $res) use ($app) {
    $db = $app->make('database');
    $users = $db->query('SELECT * FROM users');
    return $res->json($users);
});
```

### 4. Authentication System

```php
// JWT Authentication
use PivotPHP\Core\Authentication\JWTHelper;

$token = JWTHelper::encode(['user_id' => 123], 'secret');
$payload = JWTHelper::decode($token, 'secret');

// Auth middleware
$app->use(new AuthMiddleware([
    'secret' => 'your-jwt-secret',
    'algorithms' => ['HS256']
]));
```

## 🛡️ Security Features

### Built-in Security Middleware

```php
// CORS configuration
$app->use(new CorsMiddleware([
    'origin' => ['https://example.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization']
]));

// CSRF protection
$app->use(new CsrfMiddleware([
    'token_name' => '_token',
    'header_name' => 'X-CSRF-Token'
]));

// Security headers
$app->use(new SecurityHeadersMiddleware([
    'Content-Security-Policy' => "default-src 'self'",
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff'
]));
```

### Input Validation

```php
use PivotPHP\Core\Validation\Validator;

$app->post('/users', function ($req, $res) {
    $validator = new Validator($req->getParsedBody(), [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed'
    ]);

    if ($validator->fails()) {
        return $res->json(['errors' => $validator->errors()], 422);
    }

    // Create user...
});
```

## 📈 Monitoring & Logging

### Performance Monitoring

```php
use PivotPHP\Core\Monitoring\PerformanceMonitor;

// Enable monitoring
$app->use(new PerformanceMonitor([
    'enabled' => true,
    'memory_threshold' => 128 * 1024 * 1024, // 128MB
    'time_threshold' => 1.0 // 1 second
]));

// Custom metrics
PerformanceMonitor::startTimer('db_query');
// ... database operation
PerformanceMonitor::endTimer('db_query');
```

### Logging System

```php
use PivotPHP\Core\Logging\Logger;

$logger = new Logger([
    'handlers' => [
        new FileHandler('logs/app.log'),
        new ErrorHandler('logs/error.log')
    ]
]);

$app->bind('logger', $logger);

// Usage
$app->get('/test', function ($req, $res) use ($app) {
    $app->make('logger')->info('Test endpoint accessed');
    return $res->json(['status' => 'ok']);
});
```

## 🔌 Extensions & Providers

### Service Providers

```php
use PivotPHP\Core\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('database', function () {
            return new Database($_ENV['DB_DSN']);
        });
    }

    public function boot()
    {
        // Boot logic
    }
}

// Register provider
$app->register(new DatabaseServiceProvider());
```

### Extension System

```php
use PivotPHP\Core\Providers\ExtensionManager;

// Load extensions
$manager = new ExtensionManager($app);
$manager->loadExtension('cycle-orm');
$manager->loadExtension('redis-cache');
```

## 🧪 Testing

### Unit Testing

```php
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

class ApplicationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
    }

    public function testBasicRoute()
    {
        $this->app->get('/test', function () {
            return 'Hello Test';
        });

        $response = $this->app->handle(
            $this->createRequest('GET', '/test')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello Test', (string) $response->getBody());
    }
}
```

### Integration Testing

```php
class ApiTest extends TestCase
{
    public function testUserCreation()
    {
        $response = $this->post('/api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id', 'name', 'email', 'created_at'
        ]);
    }
}
```

## 📚 Advanced Usage

### Custom Exception Handling

```php
use PivotPHP\Core\Exceptions\HttpException;

$app->use(function ($req, $handler) {
    try {
        return $handler->handle($req);
    } catch (HttpException $e) {
        return new JsonResponse([
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ], $e->getStatusCode());
    }
});
```

### Database Integration

```php
// With Cycle ORM
composer require pivotphp/cycle-orm

use PivotPHP\Core\CycleORM\CycleServiceProvider;

$app->register(new CycleServiceProvider());

// Usage
$app->get('/users', function ($req, $res) {
    $users = $req->repository(User::class)->findAll();
    return $res->json($users);
});
```

### Caching

```php
use PivotPHP\Core\Cache\FileCache;
use PivotPHP\Core\Cache\MemoryCache;

// File cache
$app->bind('cache', new FileCache('cache/'));

// Memory cache
$app->bind('cache', new MemoryCache());

// Usage
$app->get('/expensive-operation', function ($req, $res) use ($app) {
    $cache = $app->make('cache');

    return $cache->remember('expensive_data', 3600, function () {
        // Expensive operation
        return ['result' => 'cached data'];
    });
});
```

## 🚀 Deployment

### Production Configuration

```php
// config/production.php
return [
    'debug' => false,
    'log_level' => 'error',
    'cache' => [
        'enabled' => true,
        'driver' => 'redis'
    ],
    'session' => [
        'driver' => 'redis',
        'lifetime' => 3600
    ]
];
```

### Docker Setup

```dockerfile
FROM php:8.1-fpm-alpine

RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html
WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader

EXPOSE 9000
CMD ["php-fpm"]
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 📖 API Reference

### Application Methods

| Method | Description |
|--------|-------------|
| `get(string $path, $handler)` | Register GET route |
| `post(string $path, $handler)` | Register POST route |
| `put(string $path, $handler)` | Register PUT route |
| `delete(string $path, $handler)` | Register DELETE route |
| `use($middleware)` | Add global middleware |
| `group(string $prefix, callable $callback)` | Create route group |
| `bind(string $key, $value)` | Bind service to container |
| `make(string $key)` | Resolve service from container |
| `run()` | Execute the application |

### Request Methods

| Method | Description |
|--------|-------------|
| `getMethod()` | Get HTTP method |
| `getUri()` | Get request URI |
| `getHeaders()` | Get all headers |
| `getHeader(string $name)` | Get specific header |
| `getParsedBody()` | Get parsed body |
| `getQueryParams()` | Get query parameters |
| `getAttribute(string $name)` | Get request attribute |

### Response Methods

| Method | Description |
|--------|-------------|
| `json(array $data, int $status = 200)` | JSON response |
| `html(string $content, int $status = 200)` | HTML response |
| `redirect(string $url, int $status = 302)` | Redirect response |
| `withStatus(int $code)` | Set status code |
| `withHeader(string $name, $value)` | Add header |

## 🤝 Contributing

### Development Setup

```bash
git clone https://github.com/PivotPHP/pivotphp-core.git
cd pivotphp-core
composer install
cp .env.example .env
```

### Running Tests

```bash
# All tests
composer test

# Specific test suite
vendor/bin/phpunit --testsuite=Unit

# Code coverage
composer test-coverage
```

### Code Quality

```bash
# PSR-12 validation
composer cs:check

# PSR-12 auto-fix
composer cs:fix

# Static analysis
composer phpstan
```

## 🔗 Resources

### Official Links
- **GitHub**: https://github.com/PivotPHP/pivotphp-core
- **Packagist**: https://packagist.org/packages/pivotphp/core
- **Documentation**: https://docs.pivotphp.com
- **Community**: https://discord.gg/pivotphp

### Extensions
- **Cycle ORM**: https://packagist.org/packages/pivotphp/cycle-orm
- **Redis Cache**: https://packagist.org/packages/pivotphp/redis-cache
- **JWT Auth**: Built-in authentication system

### Learning Resources
- [Quick Start Guide](../implementations/usage_basic.md)
- [Middleware Development](../technical/middleware/README.md)
- [Authentication Guide](../technical/authentication/usage_native.md)
- [Performance Optimization](../performance/README.md)

## 📄 License

PivotPHP is open-source software licensed under the [MIT license](LICENSE).

---

**PivotPHP v1.0.1** - Built with ❤️ for modern PHP development.

*High Performance • Type Safe • PSR Compliant • Developer Friendly*
