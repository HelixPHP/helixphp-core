# Express PHP Microframework

[![English](https://img.shields.io/badge/Language-English-blue)](README.md) [![Português](https://img.shields.io/badge/Language-Português-green)](docs/pt-br/README.md)

**Express PHP** is a lightweight, fast, and secure microframework inspired by Express.js for building modern PHP web applications and APIs.

> 🌐 **Multilingual Support**: This project is now standardized in English for better community support. Portuguese documentation is available in `docs/pt-br/`.

## 🚀 New: Modular Examples and Guided Learning

Starting from the 2025 version, Express PHP brings a collection of modular examples to facilitate learning and specialization in each framework feature. Check the `examples/` folder:

- `example_user.php`: User routes and authentication
- `example_product.php`: Product routes, parameters, and OpenAPI examples
- `example_upload.php`: File upload with practical examples
- `example_admin.php`: Administrative routes and authentication
- `example_blog.php`: Blog routes
- `example_complete.php`: Integration of all features and automatic documentation
- `example_security.php`: Security middlewares demonstration

Each example uses specialized sub-routers, facilitating isolated study of each context. Files in `examples/snippets/` can be reused in any Express PHP app.

## 📚 Automatic OpenAPI/Swagger Documentation

- **Tag grouping**: Endpoints organized by context (User, Product, Upload, Admin, Blog) in Swagger interface
- **Multiple servers**: Documentation includes local, production, and staging environments
- **Practical examples**: Request and response examples to facilitate testing and integration
- **Global responses**: All endpoints document 400, 401, 404, and 500 responses
- **Dynamic BaseUrl**: The `servers` field adjusts automatically according to environment

Access `/docs/index` for the interactive interface.

## 🎯 How to Study Each Feature

- To learn about user routes: run `php examples/example_user.php`
- For uploads: `php examples/example_upload.php`
- For products: `php examples/example_product.php`
- For admin: `php examples/example_admin.php`
- For blog: `php examples/example_blog.php`
- For security: `php examples/example_security.php`
- To see everything integrated: `php examples/example_complete.php`

## 📁 Recommended Project Structure

```
examples/           # Practical and educational examples
├── snippets/       # Ready-to-use sub-routers
SRC/               # Framework and middlewares
├── Middlewares/   # Organized middleware system
│   ├── Security/  # Security middlewares (CSRF, XSS)
│   └── Core/      # Core middlewares (CORS, Rate Limiting)
test/              # Tests and experiments
docs/              # Documentation
├── en/            # English documentation
└── pt-br/         # Portuguese documentation
```

## 💡 Quick Start

You can create your own Express PHP app by copying and adapting any example from the `examples/` folder.

```php
<?php
require_once 'vendor/autoload.php';

use Express\SRC\ApiExpress;
use Express\SRC\Middlewares\Security\SecurityMiddleware;
use Express\SRC\Middlewares\Core\CorsMiddleware;

$app = new ApiExpress();

// Apply security middleware
$app->use(SecurityMiddleware::create());

// Apply CORS
$app->use(new CorsMiddleware());

// Basic route
$app->get('/', function($req, $res) {
    $res->json(['message' => 'Hello Express PHP!']);
});

// Protected route
$app->post('/api/users', function($req, $res) {
    $userData = $req->body;
    // User data is automatically sanitized by security middleware
    $res->json(['message' => 'User created', 'data' => $userData]);
});

$app->run();
```

## 🛡️ Security Features

Express PHP includes robust security middlewares:

- **🔐 Authentication**: Automatic authentication with JWT, Basic Auth, Bearer Token, API Key support
- **CSRF Protection**: Cross-Site Request Forgery protection
- **XSS Protection**: Cross-Site Scripting sanitization
- **Security Headers**: Automatic security headers
- **Rate Limiting**: Request rate limiting
- **Session Security**: Secure session configuration

### 🆕 New: Advanced Authentication Middleware

```php
// JWT Authentication
$app->use(AuthMiddleware::jwt('your_secret_key'));

// Multiple authentication methods
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey'],
    'jwtSecret' => 'your_jwt_secret',
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

📖 **Full Documentation**: [Authentication Middleware Guide](docs/pt-br/AUTH_MIDDLEWARE.md)

## 📖 Documentation

- [🇺🇸 English Documentation](docs/en/README.md)
- [🇧🇷 Portuguese Documentation](docs/pt-br/README.md)
- [Middleware Documentation](SRC/Middlewares/README.md)
- [API Objects](docs/en/objects.md)

## 🔧 Installation & Requirements

### System Requirements
- **PHP**: 7.4.0 or higher
- **Extensions**: json, session (required)
- **Recommended**: openssl, mbstring, fileinfo, apcu

### Installation via Composer (Recommended)

1. Install via Composer:
```bash
composer require express-php/microframework
```

2. Use PSR-4 autoload in your project:
```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;
use Express\Middlewares\Security\SecurityMiddleware;

$app = new ApiExpress();
$app->use(SecurityMiddleware::create());
$app->run();
```

### Manual Installation

1. Clone the repository:
```bash
git clone https://github.com/express-php/microframework.git
cd microframework
```

2. Install dependencies:
```bash
composer install
```

3. Run an example:
```bash
php examples/example_autoload.php
```

### Testing the Installation

```bash
# Run security tests
composer run test:security

# Run authentication tests  
composer run test:auth

# Run PHPUnit tests
composer test

# Run all examples
composer run examples:complete
```

## 🌟 Features

- ✅ **Express.js-like syntax** for PHP
- ✅ **PSR-4 Autoloading** with Composer support
- ✅ **Modern PHP** (7.4+ required)
- ✅ **Automatic routing** with parameter support
- ✅ **🆕 Advanced Authentication** (JWT, Basic Auth, Bearer Token, API Key)
- ✅ **Security middlewares** (CSRF, XSS protection)
- ✅ **OpenAPI/Swagger documentation** generation
- ✅ **File upload handling**
- ✅ **CORS support**
- ✅ **Rate limiting**
- ✅ **Request validation**
- ✅ **Error handling**
- ✅ **Modular architecture**
- ✅ **PHPUnit testing** support

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Inspired by Express.js
- Built for the PHP community
- Designed for modern web development

---

**Express PHP** - Building modern PHP applications with simplicity and security.
