# Objects and Features - Express PHP

## Table of Contents
- [ApiExpress](#apiexpress)
- [Router](#router)
- [Request](#request)
- [Response](#response)
- [HeaderRequest](#headerrequest)
- [ServerExpress](#serverexpress)
- [Middlewares](#middlewares)

---

## ApiExpress
Main class for application initialization and execution.
- **Function:** Manages the application lifecycle, delegating routing and handler execution.
- **Main methods:**
  - `run()`: Starts request processing, identifies routes and executes corresponding handlers.
  - `use($middleware)`: Registers global middlewares or route grouping.
  - Magic methods to delegate route calls (`get`, `post`, etc) to the Router.
- **Example:**
```php
$app = new ApiExpress();
$app->use(function($req, $res, $next) { /* ... */ $next(); });
$app->get('/user/:id', function($req, $res) { ... });
$app->run();
```

## Router
Static class responsible for route registration and identification.
- **Function:** Allows route grouping, registering handlers and middlewares for HTTP methods and identifying the route corresponding to a request.
- **Main methods:**
  - `use($path)`: Defines a prefix/base for routes.
  - `get`, `post`, `put`, `delete`, etc: Register routes for HTTP methods, accepting multiple middlewares and final handler.
  - `identify($method, $path)`: Returns handler, middlewares and parameters for corresponding route.

## Request
Represents the received HTTP request.
- **Function:** Facilitates access to route parameters, query string, request body and headers.
- **Main properties:**
  - `$method`: HTTP method.
  - `$path`: Route pattern.
  - `$params`: Parameters extracted from URL.
  - `$query`: Query string parameters.
  - `$body`: Request body (JSON or form-data).
  - `$headers`: `HeaderRequest` instance for header access.
- **Example:**
```php
$app->get('/user/:id', function($req, $res) {
  $id = $req->params->id;
  $name = $req->query->name;
  $data = $req->body;
});
```

## Response
Represents the HTTP response to be sent.
- **Function:** Facilitates sending JSON responses, setting headers, status codes and redirects.
- **Main methods:**
  - `json($data)`: Sends JSON response.
  - `send($text)`: Sends text response.
  - `status($code)`: Sets HTTP status code.
  - `header($name, $value)`: Sets response header.
  - `redirect($url)`: Redirects to another URL.
- **Example:**
```php
$app->get('/api/users', function($req, $res) {
  $users = getUsersFromDatabase();
  $res->status(200)->json($users);
});
```

## HeaderRequest
Utility class for accessing HTTP request headers.
- **Function:** Provides convenient access to request headers with fallback support.
- **Main methods:**
  - `get($name, $default = null)`: Gets header value with optional default.
  - `has($name)`: Checks if header exists.
  - `all()`: Returns all headers as array.

## ServerExpress
Class for server configuration and initialization (if applicable).
- **Function:** Manages server settings and initialization for specific environments.

---

## Security Middlewares

### SecurityMiddleware
Combined middleware offering complete CSRF and XSS protection.
- **Function:** Applies multiple security layers in a single configuration.
- **Main features:**
  - Automatic CSRF protection
  - XSS input sanitization
  - Security headers
  - Optional rate limiting
  - Secure session configuration
- **Example:**
```php
// Basic configuration
$app->use(SecurityMiddleware::create());

// Strict configuration
$app->use(SecurityMiddleware::strict());

// Custom configuration
$app->use(new SecurityMiddleware([
    'enableCsrf' => true,
    'enableXss' => true,
    'rateLimiting' => false,
    'csrf' => ['excludePaths' => ['/api/public']],
    'xss' => ['excludeFields' => ['content']]
]));
```

### CsrfMiddleware
Specific middleware for CSRF attack protection.
- **Function:** Validates CSRF tokens in POST, PUT, PATCH and DELETE requests.
- **Main features:**
  - Automatic token generation
  - Validation via headers or body
  - Specific path exclusion
  - Utility methods for forms
- **Example:**
```php
$app->use(new CsrfMiddleware([
    'headerName' => 'X-CSRF-Token',
    'fieldName' => 'csrf_token',
    'excludePaths' => ['/webhook'],
    'methods' => ['POST', 'PUT', 'DELETE']
]));

// Get token for forms
$token = CsrfMiddleware::getToken();
$hiddenField = CsrfMiddleware::hiddenField();
$metaTag = CsrfMiddleware::metaTag();
```

### XssMiddleware
Specific middleware for XSS attack protection.
- **Function:** Sanitizes input data and adds security headers.
- **Main features:**
  - Automatic input sanitization
  - Security headers (X-XSS-Protection, CSP, etc.)
  - Malicious content detection
  - Configurable allowed HTML tags
  - URL cleaning
- **Example:**
```php
$app->use(new XssMiddleware([
    'sanitizeInput' => true,
    'securityHeaders' => true,
    'excludeFields' => ['rich_content'],
    'allowedTags' => '<p><strong><em>',
    'contentSecurityPolicy' => "default-src 'self';"
]));

// Utility methods
$clean = XssMiddleware::sanitize($input);
$hasXss = XssMiddleware::containsXss($input);
$safeUrl = XssMiddleware::cleanUrl($url);
```

### Applied Security Headers
Security middlewares automatically add:
- `X-XSS-Protection`: Browser XSS protection
- `X-Content-Type-Options`: MIME sniffing prevention  
- `X-Frame-Options`: Clickjacking protection
- `Referrer-Policy`: Referrer information control
- `Content-Security-Policy`: Content security policy

### Secure Session Configuration
SecurityMiddleware automatically configures:
- HttpOnly cookies (not accessible via JavaScript)
- Periodic session ID regeneration
- SameSite cookies for CSRF protection
- Secure lifetime parameters

## Existing Middlewares

### Middleware Chaining
- **Global middleware:**
```php
$app->use(function($req, $res, $next) {
    // Executes for all routes
    $next();
});
```

- **Route-specific middleware:**
```php
$app->get('/route',
    function($req, $res, $next) {
        // Middleware 1
        $next();
    },
    function($req, $res, $next) {
        // Middleware 2
        $next();
    },
    function($req, $res) {
        // Final handler
        $res->json(['ok' => true]);
    }
);
```

- **Chaining:**
  - Each middleware must call `$next()` to pass control forward.
  - The `$request` object can be modified between middlewares.

---

Consult the main README for overview and usage examples.
