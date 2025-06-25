# Express PHP - Complete Documentation

[![English](https://img.shields.io/badge/Language-English-blue)](README.md) [![Português](https://img.shields.io/badge/Language-Português-green)](../pt-br/README.md)

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Basic Usage](#basic-usage)
4. [Routing](#routing)
5. [Middlewares](#middlewares)
6. [Security](#security)
7. [Request and Response](#request-and-response)
8. [File Uploads](#file-uploads)
9. [OpenAPI Documentation](#openapi-documentation)
10. [Examples](#examples)
11. [API Reference](#api-reference)

## Introduction

Express PHP is a lightweight microframework for PHP that brings the simplicity and flexibility of Express.js to the PHP world. It's designed for building modern web applications and APIs with security and performance in mind.

### Key Features

- **Express.js-inspired syntax**: Familiar routing and middleware system
- **Zero dependencies**: Works without external libraries (Composer optional)
- **Built-in security**: CSRF, XSS protection, and security headers
- **Automatic documentation**: OpenAPI/Swagger generation
- **Modular architecture**: Organized middleware system
- **File upload handling**: Built-in multipart/form-data support
- **CORS support**: Cross-origin request handling

## Installation

### Option 1: Direct Download
```bash
git clone https://github.com/your-username/Express-PHP.git
cd Express-PHP
```

### Option 2: With Composer (Optional)
```bash
composer create-project express/php-framework my-app
cd my-app
```

## Basic Usage

### Creating Your First App

```php
<?php
require_once 'vendor/autoload.php'; // or include framework files

use Express\SRC\ApiExpress;

$app = new ApiExpress();

// Basic route
$app->get('/', function($req, $res) {
    $res->json(['message' => 'Hello Express PHP!']);
});

// Route with parameter
$app->get('/user/:id', function($req, $res) {
    $userId = $req->params->id;
    $res->json(['user_id' => $userId]);
});

// POST route
$app->post('/api/users', function($req, $res) {
    $userData = $req->body;
    $res->json(['message' => 'User created', 'data' => $userData]);
});

$app->run();
```

## Routing

### HTTP Methods

Express PHP supports all standard HTTP methods:

```php
$app->get('/path', $handler);
$app->post('/path', $handler);
$app->put('/path', $handler);
$app->delete('/path', $handler);
$app->patch('/path', $handler);
$app->options('/path', $handler);
```

### Route Parameters

```php
// Single parameter
$app->get('/user/:id', function($req, $res) {
    $id = $req->params->id;
    $res->json(['id' => $id]);
});

// Multiple parameters
$app->get('/user/:userId/post/:postId', function($req, $res) {
    $userId = $req->params->userId;
    $postId = $req->params->postId;
    $res->json(['user' => $userId, 'post' => $postId]);
});
```

### Query Parameters

```php
$app->get('/search', function($req, $res) {
    $query = $req->query->q ?? '';
    $page = $req->query->page ?? 1;
    $res->json(['query' => $query, 'page' => $page]);
});
// GET /search?q=express&page=2
```

### Route Groups

```php
// Group routes with prefix
$app->use('/api');

$app->get('/users', function($req, $res) {
    // This handles GET /api/users
});

$app->post('/users', function($req, $res) {
    // This handles POST /api/users
});
```

## Middlewares

### Global Middleware

```php
// Applies to all routes
$app->use(function($req, $res, $next) {
    // Middleware logic here
    echo "Global middleware executed\\n";
    $next(); // Call next middleware
});
```

### Route-specific Middleware

```php
$app->get('/protected', 
    function($req, $res, $next) {
        // Authentication middleware
        if (!isAuthenticated($req)) {
            $res->status(401)->json(['error' => 'Unauthorized']);
            return;
        }
        $next();
    },
    function($req, $res) {
        // Route handler
        $res->json(['message' => 'Protected content']);
    }
);
```

### Built-in Middlewares

#### CORS Middleware

```php
use Express\SRC\Middlewares\Core\CorsMiddleware;

$app->use(new CorsMiddleware([
    'origin' => ['http://localhost:3000', 'https://myapp.com'],
    'methods' => 'GET,POST,PUT,DELETE',
    'headers' => 'Content-Type,Authorization',
    'credentials' => true
]));
```

#### Rate Limiting

```php
use Express\SRC\Middlewares\Core\RateLimitMiddleware;

$app->use(new RateLimitMiddleware([
    'max' => 100,        // 100 requests
    'window' => 60       // per 60 seconds
]));
```

#### Error Handling

```php
use Express\SRC\Middlewares\Core\ErrorHandlerMiddleware;

$app->use(new ErrorHandlerMiddleware([
    'debug' => true,     // Show detailed errors in development
    'logErrors' => true  // Log errors to file
]));
```

## Security

Express PHP includes comprehensive security features:

### Security Middleware (All-in-One)

```php
use Express\SRC\Middlewares\Security\SecurityMiddleware;

// Basic security (recommended)
$app->use(SecurityMiddleware::create());

// Strict security (maximum protection)
$app->use(SecurityMiddleware::strict());

// Custom configuration
$app->use(new SecurityMiddleware([
    'enableCsrf' => true,
    'enableXss' => true,
    'rateLimiting' => false,
    'csrf' => [
        'excludePaths' => ['/api/webhook'],
        'generateTokenResponse' => true
    ],
    'xss' => [
        'excludeFields' => ['rich_content'],
        'allowedTags' => '<p><strong><em>'
    ]
]));
```

### CSRF Protection

```php
use Express\SRC\Middlewares\Security\CsrfMiddleware;

$app->use(new CsrfMiddleware([
    'headerName' => 'X-CSRF-Token',
    'fieldName' => 'csrf_token',
    'excludePaths' => ['/api/public'],
    'methods' => ['POST', 'PUT', 'PATCH', 'DELETE']
]));

// Get CSRF token for forms
$app->get('/token', function($req, $res) {
    $token = CsrfMiddleware::getToken();
    $res->json(['csrf_token' => $token]);
});
```

### XSS Protection

```php
use Express\SRC\Middlewares\Security\XssMiddleware;

$app->use(new XssMiddleware([
    'sanitizeInput' => true,
    'securityHeaders' => true,
    'excludeFields' => ['content'],
    'allowedTags' => '<p><br><strong><em>',
    'contentSecurityPolicy' => "default-src 'self'"
]));

// Manual sanitization
$clean = XssMiddleware::sanitize($userInput);
$safe = XssMiddleware::cleanUrl($url);
```

### Security Headers

Automatically applied security headers:

- `X-XSS-Protection: 1; mode=block`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy: [configurable]`

## Request and Response

### Request Object

```php
$app->post('/api/data', function($req, $res) {
    // HTTP method
    $method = $req->method;
    
    // Route parameters
    $id = $req->params->id;
    
    // Query parameters
    $search = $req->query->search;
    
    // Request body (JSON or form data)
    $data = $req->body;
    
    // Headers
    $authHeader = $req->headers->get('Authorization');
    
    // Files (multipart uploads)
    $file = $req->file('avatar');
});
```

### Response Object

```php
$app->get('/api/users', function($req, $res) {
    // JSON response
    $res->json(['users' => $users]);
    
    // Status code
    $res->status(201)->json(['created' => true]);
    
    // Headers
    $res->header('Custom-Header', 'value');
    
    // Text response
    $res->send('Hello World');
    
    // File download
    $res->download('/path/to/file.pdf');
    
    // Redirect
    $res->redirect('/login');
});
```

## File Uploads

### Basic Upload Handling

```php
use Express\SRC\Middlewares\Core\AttachmentMiddleware;

// Enable file upload middleware
$app->use(new AttachmentMiddleware([
    'uploadDir' => 'uploads/',
    'maxSize' => 10 * 1024 * 1024, // 10MB
    'allowedTypes' => ['image/jpeg', 'image/png', 'application/pdf']
]));

$app->post('/upload', function($req, $res) {
    $file = $req->file('document');
    
    if ($file) {
        // File info
        $name = $file['name'];
        $size = $file['size'];
        $type = $file['type'];
        $tmpPath = $file['tmp_name'];
        
        // Move to permanent location
        $uploadPath = 'uploads/' . $name;
        if (move_uploaded_file($tmpPath, $uploadPath)) {
            $res->json(['message' => 'File uploaded successfully']);
        } else {
            $res->status(500)->json(['error' => 'Upload failed']);
        }
    } else {
        $res->status(400)->json(['error' => 'No file provided']);
    }
});
```

### Multiple File Uploads

```php
$app->post('/upload-multiple', function($req, $res) {
    $files = $req->files('documents'); // Array of files
    
    foreach ($files as $file) {
        // Process each file
        $uploadPath = 'uploads/' . $file['name'];
        move_uploaded_file($file['tmp_name'], $uploadPath);
    }
    
    $res->json(['message' => 'All files uploaded']);
});
```

## OpenAPI Documentation

### Automatic Documentation Generation

```php
use Express\SRC\Middlewares\Core\OpenApiDocsMiddleware;

$app->use(new OpenApiDocsMiddleware([
    'title' => 'My API',
    'version' => '1.0.0',
    'description' => 'API documentation',
    'docsPath' => '/docs'
]));

// Routes with OpenAPI metadata
$app->get('/api/users/:id', 
    function($req, $res) {
        $res->json(['user' => $user]);
    },
    [
        'summary' => 'Get user by ID',
        'parameters' => [
            'id' => ['type' => 'integer', 'description' => 'User ID']
        ],
        'responses' => [
            200 => ['description' => 'User found'],
            404 => ['description' => 'User not found']
        ],
        'tags' => ['Users']
    ]
);
```

### Accessing Documentation

- Swagger UI: `GET /docs/index`
- OpenAPI JSON: `GET /docs/openapi.json`
- Redoc UI: `GET /docs/redoc`

## Examples

The framework includes comprehensive examples in the `examples/` folder:

### Running Examples

```bash
# User management example
php examples/example_user.php

# Product catalog example  
php examples/example_product.php

# File upload example
php examples/example_upload.php

# Security demonstration
php examples/example_security.php

# Complete integration example
php examples/example_complete.php
```

### Example Structure

Each example demonstrates:
- Route definitions
- Middleware usage
- Request/response handling
- Security implementation
- OpenAPI documentation

## API Reference

### ApiExpress Class

Main application class for creating Express PHP applications.

```php
$app = new ApiExpress($baseUrl = null);
```

#### Methods

- `get($path, ...$handlers)` - Register GET route
- `post($path, ...$handlers)` - Register POST route
- `put($path, ...$handlers)` - Register PUT route
- `delete($path, ...$handlers)` - Register DELETE route
- `patch($path, ...$handlers)` - Register PATCH route
- `options($path, ...$handlers)` - Register OPTIONS route
- `use($middleware)` - Register global middleware
- `run()` - Start the application

### Request Object Properties

- `$method` - HTTP method (GET, POST, etc.)
- `$path` - Request path
- `$params` - Route parameters object
- `$query` - Query parameters object  
- `$body` - Request body (parsed JSON/form data)
- `$headers` - Headers object

### Response Object Methods

- `json($data)` - Send JSON response
- `send($text)` - Send text response
- `status($code)` - Set status code
- `header($name, $value)` - Set header
- `redirect($url)` - Redirect request
- `download($file)` - Send file download

### Utility Classes

#### Utils

Static utility methods for common operations:

```php
use Express\SRC\Helpers\Utils;

Utils::sanitizeString($input);
Utils::isEmail($email);
Utils::csrfToken();
Utils::checkCsrf($token);
Utils::randomToken($length);
```

For more detailed API documentation, see the [Objects Documentation](objects.md).

---

**Express PHP** - Simple, secure, and powerful PHP framework for modern web development.
