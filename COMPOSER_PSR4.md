# Composer & PSR-4 Implementation

This document details the Composer and PSR-4 autoload implementation in Express PHP.

## 📦 Package Information

**Package Name**: `express-php/microframework`
**PSR-4 Namespace**: `Express\`
**Minimum PHP Version**: 7.4.0

## 🗂️ PSR-4 Namespace Structure

```
Express\                        # Root namespace (SRC/)
├── ApiExpress                  # Main framework class
├── Controller\                 # Controllers
│   ├── Router
│   └── RouterInstance
├── Services\                   # Core services
│   ├── Request
│   ├── Response
│   ├── HeaderRequest
│   └── OpenApiExporter
├── Middlewares\               # Middleware system
│   ├── Security\              # Security middlewares
│   │   ├── CsrfMiddleware
│   │   ├── XssMiddleware
│   │   └── SecurityMiddleware
│   └── Core\                  # Core middlewares
│       ├── CorsMiddleware
│       ├── RateLimitMiddleware
│       ├── ErrorHandlerMiddleware
│       ├── OpenApiDocsMiddleware
│       ├── AttachmentMiddleware
│       └── RequestValidationMiddleware
└── Helpers\                   # Utility helpers
    └── Utils
```

## 🚀 Usage Examples

### Basic Usage
```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;

$app = new ApiExpress();
$app->get('/', function($req, $res) {
    $res->json(['message' => 'Hello Express PHP!']);
});
$app->run();
```

### With Middlewares
```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Core\CorsMiddleware;

$app = new ApiExpress();
$app->use(SecurityMiddleware::create());
$app->use(new CorsMiddleware());
$app->run();
```

### Router Instance
```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;
use Express\Controller\RouterInstance;

$app = new ApiExpress();
$router = new RouterInstance('/api');

$router->get('/users', function($req, $res) {
    $res->json(['users' => []]);
});

$app->use($router);
$app->run();
```

## 📋 Composer Commands

```bash
# Install package
composer require express-php/microframework

# Development dependencies
composer install

# Run tests
composer test
composer run test:security

# Code quality
composer run phpstan
composer run cs:check
composer run cs:fix

# Examples
composer run examples:security
composer run examples:complete
```

## 🔄 Migration from Old Namespaces

### Before (Old):
```php
use Express\SRC\ApiExpress;
use Express\SRC\Middlewares\Security\CsrfMiddleware;
use Express\SRC\Controller\Router;
```

### After (New PSR-4):
```php
use Express\ApiExpress;
use Express\Middlewares\Security\CsrfMiddleware;
use Express\Controller\Router;
```

## 🧪 Testing with PHPUnit

### Running Tests
```bash
# All tests
php vendor/bin/phpunit

# Specific test
php vendor/bin/phpunit tests/Security/CsrfMiddlewareTest.php

# With coverage (requires Xdebug)
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-html coverage-html
```

### Test Structure
```
tests/
├── Security/
│   ├── CsrfMiddlewareTest.php
│   └── XssMiddlewareTest.php
└── Core/
    └── RateLimitMiddlewareTest.php
```

## 📚 Configuration Files

### composer.json
- Package metadata and dependencies
- PSR-4 autoload configuration
- Development scripts and tools

### phpunit.xml
- PHPUnit test configuration
- Test suites organization
- Coverage settings

### phpstan.neon
- Static analysis configuration
- Code quality rules
- Type checking settings

## 🔧 Development Tools

### Quality Assurance
- **PHPUnit**: Unit testing framework
- **PHPStan**: Static analysis tool
- **PHP_CodeSniffer**: Code style checker

### Composer Scripts
- `test`: Run PHPUnit tests
- `test:security`: Run security middleware tests
- `phpstan`: Run static analysis
- `cs:check`: Check code style
- `cs:fix`: Fix code style automatically

## 🌟 Benefits

1. **Standard Compliance**: Follows PSR-4 autoloading standard
2. **Better IDE Support**: Improved autocomplete and navigation
3. **Easy Integration**: Simple Composer installation
4. **Modern PHP**: Leverages modern PHP features and tools
5. **Quality Assurance**: Built-in testing and code quality tools

## 📖 Documentation

- [Composer Documentation](https://getcomposer.org/doc/)
- [PSR-4 Specification](https://www.php-fig.org/psr/psr-4/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
