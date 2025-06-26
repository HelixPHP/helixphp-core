# Middlewares - Express PHP

[![English](https://img.shields.io/badge/Language-English-blue)](README.md) [![Português](https://img.shields.io/badge/Language-Português-green)](../../docs/pt-br/middlewares.md)

This folder contains all middlewares organized by category for the Express PHP framework.

## 📁 Folder Structure

### 🔒 Security/
Security-related middlewares:

- **CsrfMiddleware.php** - CSRF attack protection
- **XssMiddleware.php** - XSS attack protection
- **SecurityMiddleware.php** - Combined security middleware

### ⚙️ Core/
Framework core middlewares:

- **AttachmentMiddleware.php** - File upload management
- **CorsMiddleware.php** - CORS configuration
- **ErrorHandlerMiddleware.php** - Global error handling
- **OpenApiDocsMiddleware.php** - Automatic OpenAPI/Swagger documentation
- **RateLimitMiddleware.php** - Request rate limiting
- **RequestValidationMiddleware.php** - Request validation

## 🚀 How to Use

### Individual Import

```php
// Security Middlewares
use Express\SRC\Middlewares\Security\SecurityMiddleware;
use Express\SRC\Middlewares\Security\CsrfMiddleware;
use Express\SRC\Middlewares\Security\XssMiddleware;

// Core Middlewares
use Express\SRC\Middlewares\Core\CorsMiddleware;
use Express\SRC\Middlewares\Core\RateLimitMiddleware;
```

### Complete Import

```php
// Import all middlewares
require_once 'SRC/Middlewares/index.php';
```

### Application in Examples

```php
$app = new ApiExpress();

// Complete security
$app->use(SecurityMiddleware::create());

// CORS
$app->use(new CorsMiddleware());

// Rate limiting
$app->use(new RateLimitMiddleware());
```

## 🔄 Compatibility

To maintain compatibility with existing code, aliases are automatically created:

- `Express\SRC\Services\SecurityMiddleware` → `Express\SRC\Middlewares\Security\SecurityMiddleware`
- `Express\SRC\Services\CsrfMiddleware` → `Express\SRC\Middlewares\Security\CsrfMiddleware`
- `Express\SRC\Services\XssMiddleware` → `Express\SRC\Middlewares\Security\XssMiddleware`
- And all other middlewares...

## 📚 Documentation

For detailed documentation of each middleware, see:

- [🇺🇸 Main README](../../README.md)
- [🇧🇷 Portuguese README](../../docs/pt-br/README.md)
- [Objects documentation](../../docs/en/objects.md)
- [Practical examples](../../examples/)

## 🆕 New Middlewares

To add new middlewares:

1. **Security**: Place in `Security/` if related to protection/sanitization
2. **Core**: Place in `Core/` if it's fundamental functionality
3. **Future categories**: Create new folders as needed

### Template for New Middleware

```php
<?php
namespace Express\SRC\Middlewares\[Category];

class NewMiddleware
{
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            // default options
        ], $options);
    }

    public function __invoke($request, $response, $next)
    {
        // middleware logic
        $next();
    }
}
```

## 🧪 Tests

Run specific tests:

```bash
php test/security_test.php  # Security middlewares
```

## 📋 Migration Checklist

If you're migrating existing code:

- [ ] Update imports from `Express\SRC\Services\*` to `Express\SRC\Middlewares\[Category]\*`
- [ ] Check if aliases work correctly
- [ ] Run tests to ensure functionality
- [ ] Update documentation if necessary

---

**Note**: This reorganization maintains 100% compatibility with existing code through automatic aliases.
