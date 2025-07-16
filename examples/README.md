# PivotPHP Core v1.2.0 - Complete Examples Collection 🚀

This directory contains production-ready examples that demonstrate the full potential of PivotPHP Core v1.2.0, including simplified performance mode and clean architecture.

## 🎯 What's New in v1.2.0

- **Simplified Performance**: Clean performance mode following "Simplicidade sobre Otimização Prematura"
- **Architectural Excellence**: Removed enterprise complexity, focused on microframework essentials
- **100% Test Coverage**: All tests passing, comprehensive CI/CD pipeline
- **Enhanced Documentation**: Updated examples and clear deprecation warnings
- **100% Backward Compatibility**: All existing code continues to work via aliases

## 📁 Examples Structure

### 01-basics - Fundamentals
- **hello-world.php** - Simplest possible example
- **basic-routes.php** - GET, POST, PUT, DELETE with complete CRUD
- **request-response.php** - Advanced Request/Response handling
- **json-api.php** - JSON API with validation and consistent structures

### 02-routing - Advanced Routing
- **regex-routing.php** - Custom regex routes with pattern validation
- **route-parameters.php** - Required, optional parameters and query strings
- **route-groups.php** - Route grouping with shared middleware
- **route-constraints.php** - Advanced constraints and parameter validation
- **static-files.php** - NEW! `$app->staticFiles()` and `$app->static()` demonstration

### 03-middleware - Custom Middleware
- **custom-middleware.php** - Custom middleware (logging, validation, transformation)
- **middleware-stack.php** - Complex stacks and execution order
- **auth-middleware.php** - JWT, API Key, Session and Basic Auth
- **cors-middleware.php** - Advanced CORS with dynamic policies

### 04-api - Complete APIs
- **rest-api.php** - Complete RESTful API with pagination, filters and validation

### 05-performance - Performance & Optimization
- **high-performance.php** - v1.2.0 simplified performance mode, JSON optimization, metrics

### 06-security - Security
- **jwt-auth.php** - Complete JWT system with refresh tokens and authorization

### 07-advanced - v1.2.0 Advanced Features ✨
- **array-callables.php** - Array callable syntax demonstration
- **performance-v1.1.3.php** - Performance simplificada showcase (updated to v1.2.0)

## 🚀 Quick Start

### Prerequisites
```bash
# Ensure you're in the project root
cd /path/to/pivotphp-core
composer install
```

### Running Examples

#### 🆕 v1.1.3 New Features
```bash
# Array callable syntax (NEW!)
php -S localhost:8000 examples/07-advanced/array-callables.php
curl http://localhost:8000/users                    # Instance method callable
curl http://localhost:8000/admin/dashboard          # Static method callable

# Performance improvements showcase (NEW!)
php -S localhost:8000 examples/07-advanced/performance-v1.1.3.php
curl http://localhost:8000/performance/metrics      # Real-time performance data
curl http://localhost:8000/performance/json/large   # JSON optimization demo
```

#### 🔥 Popular Examples
```bash
# Hello World - Start here
php -S localhost:8000 examples/01-basics/hello-world.php
curl http://localhost:8000/

# Complete CRUD API
php -S localhost:8000 examples/01-basics/basic-routes.php
curl -X POST http://localhost:8000/users -H "Content-Type: application/json" -d '{"name":"John"}'

# Static files and routes (NEW!)
php -S localhost:8000 examples/02-routing/static-files.php
curl http://localhost:8000/public/test.json          # File serving
curl http://localhost:8000/api/static/health         # Pre-compiled response

# Advanced routing with regex
php -S localhost:8000 examples/02-routing/regex-routing.php
curl http://localhost:8000/users/123
curl http://localhost:8000/products/my-awesome-product

# JWT Authentication
php -S localhost:8000 examples/06-security/jwt-auth.php
curl -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"username":"admin","password":"secret123"}'
```

### Example Structure
Each example file contains:
- ✅ **Complete functional code** - Ready to run
- 📝 **Detailed explanatory comments** - Inline documentation
- 🧪 **Test instructions** - Ready-to-use curl commands
- 🎯 **Real-world use cases** - Practical implementation examples
- ⚡ **v1.1.3 features** - Latest framework capabilities
- 🔒 **Best practices** - Security and performance guidelines

## 🎯 Featured Examples

### 🆕 Array Callables (v1.1.3)
```php
class UserController {
    public function index($req, $res) {
        return $res->json(['users' => User::all()]);
    }
}

// NEW: Array callable syntax
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [$controller, 'store']);
```

### ⚡ Performance Optimization (v1.1.3)
```php
// Automatic JSON optimization
$app->get('/api/data', function($req, $res) {
    $largeDataset = Database::getAllRecords(); // 1000+ records
    return $res->json($largeDataset); // Automatically uses buffer pooling!
});
```

### 🔐 Robust Security
```php
// JWT with refresh tokens
$app->post('/auth/login', function($req, $res) {
    $credentials = $req->getBodyAsStdClass();
    $tokens = AuthService::authenticate($credentials);
    return $res->json($tokens);
});
```

### 🎯 Advanced Routing
```php
// Regex constraints with parameters
$app->get('/users/:id<\\d+>', function($req, $res) {
    $userId = $req->param('id'); // Guaranteed to be numeric
    return $res->json(['user' => User::find($userId)]);
});
```

## 📊 Performance Showcase

### v1.1.3 Improvements
- **Framework Throughput**: 20,400 → 44,092 ops/sec (+116%)
- **Object Pool Reuse**: 0% → 100% (Request), 0% → 99.9% (Response)
- **JSON Operations**: 505K ops/sec (small), 214K ops/sec (large) - Internal benchmarks
- **Docker Validated**: 6,227 req/sec in standardized containers (3rd place competitive)

### Docker Framework Comparison
| Framework | Performance | Position |
|-----------|-------------|----------|
| Slim 4 | 6,881 req/sec | 1st |
| Lumen | 6,322 req/sec | 2nd |
| **PivotPHP** | **6,227 req/sec** | **3rd** |
| Flight | 3,179 req/sec | 4th |

*PivotPHP: 9.5% behind leader, 96% faster than Flight*

## 🛠️ Available Features Demo

### Express.js-Inspired API
- Familiar and intuitive syntax
- Zero configuration to get started
- PSR-7/PSR-15 compliance

### v1.1.3 Performance Features
- Automatic JSON buffer pooling
- Object pooling for Request/Response
- Integrated memory optimizations
- Smart garbage collection

### Robust Security
- JWT with refresh tokens
- Flexible authentication middleware
- Dynamic and configurable CORS
- XSS and CSRF protection

### Advanced Routing
- Custom regex with validation
- Optional parameters and wildcards
- Constraints and automatic validation
- Route groups and middleware

### Powerful Middleware
- Configurable middleware stack
- Data transformation and validation
- Integrated logging and monitoring
- Performance and security middleware

### Production-Ready APIs
- Complete RESTful with pagination
- Automatic input validation
- Correct headers and status codes
- Error handling and logging

## 🆘 Support & Documentation

- 💬 **Discord**: [Join our community](https://discord.gg/DMtxsP7z)
- 📚 **Documentation**: [Complete guides](../docs/README.md)
- 🐛 **Issues**: [GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)
- 🚀 **Quick Start**: [5-minute setup guide](../docs/quick-start.md)

## 📋 Examples Summary

| Category | Files | Key Features Demonstrated |
|----------|-------|---------------------------|
| **01-basics** | 4 files | Hello World, CRUD, Request/Response, JSON API |
| **02-routing** | 5 files | Regex, Parameters, Groups, Constraints, Static Files |
| **03-middleware** | 4 files | Custom, Stack, Complete Auth, CORS |
| **04-api** | 1 file | Complete REST with pagination and filters |
| **05-performance** | 1 file | High Performance Mode v1.1.0+ |
| **06-security** | 1 file | Complete JWT with refresh tokens |
| **07-advanced** | 2 files | **NEW!** Array callables, Performance v1.1.3 |
| **Total** | **17 examples** | **Complete framework coverage** |

## 🔄 Migration from Previous Versions

### From v1.1.2 to v1.1.3
```php
// OLD: Only closure syntax
$app->get('/users', function($req, $res) {
    return UserController::index($req, $res);
});

// NEW: Clean array callable syntax
$app->get('/users', [UserController::class, 'index']);
```

### Performance Improvements (Automatic)
- Object pooling improvements are automatic
- JSON optimization works transparently
- No code changes required
- Immediate +116% performance boost

## 🎯 Best Practices Demonstrated

1. **Security First**: All examples include proper input validation and security headers
2. **Performance Optimized**: Leverages v1.1.3 automatic optimizations
3. **Type Safety**: PHP 8.1+ features with strict typing
4. **PSR Compliance**: PSR-7, PSR-15, PSR-12 standards followed
5. **Real-World Ready**: Production-grade error handling and logging

---

**PivotPHP Core v1.1.3** - Express.js for PHP with revolutionary performance! 🐘⚡  
**Examples updated:** July 2025