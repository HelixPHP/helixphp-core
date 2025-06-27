# Express PHP - Complete Documentation

[![English](https://img.shields.io/badge/Language-English-blue)](README.md) [![Português](https://img.shields.io/badge/Language-Português-green)](../pt-br/README.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](https://phpstan.org/)

**Express PHP** is a lightweight, fast, and secure microframework inspired by Express.js for building modern web applications and APIs in PHP with native multi-method authentication system.

> 🔐 **New in v1.0**: Complete authentication system with JWT, Basic Auth, Bearer Token, API Key, and auto-detection!

## 🚀 Quick Start

### Installation

```bash
composer require cafernandes/express-php
```

### Basic Example

```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;
use Express\Middlewares\Security\SecurityMiddleware;

$app = new ApiExpress();

// Apply security middleware
$app->use(SecurityMiddleware::create());

// Basic route
$app->get('/', function($req, $res) {
    $res->json(['message' => 'Hello Express PHP!']);
});

// Protected route with authentication
$app->post('/api/users', function($req, $res) {
    // Data automatically sanitized by security middleware
    $userData = $req->body;
    $res->json(['message' => 'User created', 'data' => $userData]);
});

$app->run();
```

## ✨ Key Features

- 🔐 **Multi-method Authentication**: JWT, Basic Auth, Bearer Token, API Key
- 🛡️ **Advanced Security**: CSRF, XSS, Rate Limiting, Security Headers
- 📡 **Streaming**: Complete support for data streaming, SSE and large files
- 📚 **OpenAPI/Swagger Documentation**: Automatic documentation generation
- 🎯 **Modular Middlewares**: Flexible middleware system
- ⚡ **Performance**: Optimized for high performance
- 🧪 **Tested**: 186+ unit tests and 100% code coverage
- 📊 **Static Analysis**: PHPStan Level 8 compliance

## 📖 Documentation

- **[🚀 Getting Started](../guides/starter/)** - Start here!
- **[📚 Complete Documentation (PT-BR)](../pt-br/README.md)** - Detailed documentation
- **[🔐 Authentication System](../pt-br/AUTH_MIDDLEWARE.md)** - Authentication guide
- **[📡 Data Streaming](../pt-br/STREAMING.md)** - Streaming and Server-Sent Events
- **[🛡️ Security Middlewares](../guides/SECURITY_IMPLEMENTATION.md)** - Security guide
- **[📝 Practical Examples](../../examples/)** - Ready-to-use examples

## 🎯 Learning Examples

The framework includes modular examples to facilitate learning:

- **[👥 Users](../../examples/example_user.php)** - User routes and authentication
- **[📦 Products](../../examples/example_product.php)** - CRUD and route parameters
- **[📤 Upload](../../examples/example_upload.php)** - File uploads
- **[🔐 Admin](../../examples/example_admin.php)** - Administrative routes
- **[📝 Blog](../../examples/example_blog.php)** - Blog system
- **[📡 Streaming](../../examples/example_streaming.php)** - Data streaming and SSE
- **[🛡️ Security](../../examples/example_security.php)** - Middleware demonstration
- **[🏗️ Complete](../../examples/example_complete.php)** - Integration of all features

## 🛡️ Authentication System

```php
// JWT Authentication
$app->use(AuthMiddleware::jwt('your_secret_key'));

// Multiple authentication methods
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey'],
    'jwtSecret' => 'your_jwt_key',
    'basicAuthCallback' => 'validateUser',
    'apiKeyCallback' => 'validateApiKey'
]));

// Access authenticated user data
$app->get('/profile', function($req, $res) {
    $user = $req->user; // authenticated user data
    $method = $req->auth['method']; // auth method used
    $res->json(['user' => $user, 'auth_method' => $method]);
});
```

## 📡 Data Streaming

Express PHP offers complete support for real-time data streaming:

```php
// Simple text streaming
$app->get('/stream/text', function($req, $res) {
    $res->startStream('text/plain; charset=utf-8');

    for ($i = 1; $i <= 10; $i++) {
        $res->write("Chunk {$i}\n");
        sleep(1); // Simulate processing
    }

    $res->endStream();
});

// Server-Sent Events (SSE)
$app->get('/events', function($req, $res) {
    $res->sendEvent('Connection established', 'connect');

    for ($i = 1; $i <= 10; $i++) {
        $data = ['counter' => $i, 'timestamp' => time()];
        $res->sendEvent($data, 'update', (string)$i);
        sleep(1);
    }
});
```

## 🔧 Basic Routing

```php
// HTTP Methods
$app->get('/users', function($req, $res) {
    $res->json(['users' => []]);
});

$app->post('/users', function($req, $res) {
    $userData = $req->body;
    $res->json(['created' => $userData]);
});

// Route parameters
$app->get('/user/:id', function($req, $res) {
    $id = $req->params['id'];
    $res->json(['user_id' => $id]);
});

// Query parameters
$app->get('/search', function($req, $res) {
    $query = $req->query['q'] ?? '';
    $res->json(['search' => $query]);
});
```

## 🛡️ Security Middlewares

```php
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Core\CorsMiddleware;

// Complete security in one line
$app->use(SecurityMiddleware::create());

// Custom CORS
$app->use(new CorsMiddleware([
    'origin' => ['http://localhost:3000'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization']
]));
```

### Security Features
- Automatic input sanitization
- XSS protection
- CSRF prevention
- Configurable rate limiting
- Automatic security headers
- Robust input validation

## 📚 API Reference

### Main Classes

- **[ApiExpress](objects.md#apiexpress)** - Main framework class
- **[Request](objects.md#request)** - HTTP request object
- **[Response](objects.md#response)** - HTTP response object
- **[Router](objects.md#router)** - Routing system

### Middlewares

- **[AuthMiddleware](objects.md#authmiddleware)** - Authentication
- **[SecurityMiddleware](objects.md#securitymiddleware)** - Security
- **[CorsMiddleware](objects.md#corsmiddleware)** - CORS
- **[RateLimitMiddleware](objects.md#ratelimitmiddleware)** - Rate limiting

For complete API reference, see [objects.md](objects.md).

## ⚙️ Requirements

- **PHP**: 8.0.0 or higher
- **Extensions**: json, session
- **Recommended**: openssl, mbstring, fileinfo

## 🧪 Testing

The project has complete test coverage:

```bash
# Run all tests
composer test

# Run tests with coverage
composer test:coverage

# Static analysis with PHPStan
composer analyze
```

### Test Statistics
- **186 tests** executed
- **100% coverage**
- **PHPStan Level 8** (maximum)
- Unit and integration tests

## 🌟 Quality & Features

- ✅ **PHP 7.4+** compatible
- ✅ **PHPStan Level 8** compliance
- ✅ **PSR-12** code style
- ✅ **186 unit tests**
- ✅ **Multi-method authentication** (JWT, Basic, API Key, Bearer)
- ✅ **Security middlewares** (CSRF, XSS, Rate Limiting)
- ✅ **Server-Sent Events** (SSE) streaming
- ✅ **Zero required dependencies**
- ✅ **Optimized performance**

## 🤝 Contributing

Contributions are welcome! See our [contribution guide](../../CONTRIBUTING.md).

## 📄 License

This project is licensed under the [MIT License](../../LICENSE).

## 🌟 Support

- [Issues](https://github.com/CAFernandes/express-php/issues) - Report bugs or request features
- [Discussions](https://github.com/CAFernandes/express-php/discussions) - Questions and discussions
- [Wiki](https://github.com/CAFernandes/express-php/wiki) - Additional documentation

---

**🚀 Ready to start?** Follow our [quick start guide](../guides/starter/)!
