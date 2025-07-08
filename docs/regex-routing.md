# Regex Routing in PivotPHP

PivotPHP now supports regex-based route constraints, allowing you to define more precise URL patterns and validate parameters at the routing level.

## Table of Contents
- [Basic Usage](#basic-usage)
- [Constraint Syntax](#constraint-syntax)
- [Built-in Shortcuts](#built-in-shortcuts)
- [Advanced Patterns](#advanced-patterns)
- [Security Considerations](#security-considerations)
- [Performance Impact](#performance-impact)
- [Migration Guide](#migration-guide)

## Basic Usage

### Simple Constraints

Add constraints to route parameters using the `<constraint>` syntax:

```php
// Only matches numeric IDs
Router::get('/users/:id<\d+>', function($req, $res) {
    $userId = $req->param('id'); // Guaranteed to be numeric
    return $res->json(['user_id' => $userId]);
});

// Only matches lowercase slugs with hyphens
Router::get('/posts/:slug<[a-z0-9-]+>', function($req, $res) {
    $slug = $req->param('slug');
    return $res->json(['slug' => $slug]);
});
```

### Multiple Constrained Parameters

```php
Router::get('/archive/:year<\d{4}>/:month<\d{2}>/:day<\d{2}>', function($req, $res) {
    return $res->json([
        'year' => $req->param('year'),   // e.g., "2024"
        'month' => $req->param('month'), // e.g., "01"
        'day' => $req->param('day')      // e.g., "15"
    ]);
});
```

## Constraint Syntax

PivotPHP supports three ways to define route constraints:

### 1. Inline Constraints (Recommended)
```php
Router::get('/users/:id<\d+>', $handler);
```

### 2. Constraint Shortcuts
```php
Router::get('/users/:id<int>', $handler);
Router::get('/posts/:slug<slug>', $handler);
```

### 3. Full Regex Patterns (Advanced)
```php
Router::get('/files/{^(.+)\.(jpg|png|gif)$}', $handler);
```

## Built-in Shortcuts

PivotPHP provides convenient shortcuts for common patterns:

| Shortcut | Regex Pattern | Description |
|----------|---------------|-------------|
| `int` | `\d+` | One or more digits |
| `slug` | `[a-z0-9-]+` | Lowercase letters, numbers, and hyphens |
| `alpha` | `[a-zA-Z]+` | Letters only |
| `alnum` | `[a-zA-Z0-9]+` | Letters and numbers |
| `uuid` | `[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}` | UUID format |
| `date` | `\d{4}-\d{2}-\d{2}` | Date in YYYY-MM-DD format |
| `year` | `\d{4}` | 4-digit year |
| `month` | `\d{2}` | 2-digit month |
| `day` | `\d{2}` | 2-digit day |

### Examples with Shortcuts

```php
// Using shortcuts
Router::get('/users/:id<int>', $handler);          // Same as <\d+>
Router::get('/posts/:slug<slug>', $handler);       // Same as <[a-z0-9-]+>
Router::get('/api/:uuid<uuid>', $handler);         // UUID validation
Router::get('/archive/:date<date>', $handler);     // Date validation
```

## Advanced Patterns

### File Extensions
```php
Router::get('/files/:filename<[\w-]+>.:ext<jpg|png|gif|webp>', function($req, $res) {
    return $res->json([
        'filename' => $req->param('filename'),
        'extension' => $req->param('ext')
    ]);
});
```

### Email-like Patterns
```php
Router::get('/contact/:email<[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+>', $handler);
```

### API Versioning
```php
Router::get('/api/:version<v\d+>/users', function($req, $res) {
    $version = $req->param('version'); // e.g., "v1", "v2"
    return $res->json(['api_version' => $version]);
});
```

### ISBN Validation
```php
Router::get('/books/:isbn<\d{3}-\d{10}>', function($req, $res) {
    $isbn = $req->param('isbn'); // e.g., "978-0123456789"
    return $res->json(['isbn' => $isbn]);
});
```

### Complex Path Matching
```php
// Using full regex syntax
Router::get('/archive/{^(\d{4})/(\d{2})/(.+)$}', function($req, $res) {
    $captures = $req->captures(); // Array of captured groups
    return $res->json([
        'year' => $captures[0],
        'month' => $captures[1],
        'slug' => $captures[2]
    ]);
});
```

## Security Considerations

### ReDoS Protection

PivotPHP automatically protects against Regular Expression Denial of Service (ReDoS) attacks:

```php
// This will throw an InvalidArgumentException
Router::get('/test/:param<(\w+)*\w*>', $handler); // Dangerous pattern

// Safe patterns are allowed
Router::get('/test/:param<\w+>', $handler); // OK
```

### Pattern Validation

- Maximum pattern length: 200 characters
- Nested quantifiers are blocked
- Excessive alternations (>10) are blocked
- Known dangerous patterns are rejected

### Best Practices

1. Use built-in shortcuts when possible
2. Keep patterns simple and specific
3. Avoid complex nested patterns
4. Test patterns thoroughly
5. Consider performance impact

## Performance Impact

### Overhead Analysis

Based on benchmarks with 10,000 iterations:

| Route Type | Performance Impact |
|------------|-------------------|
| Simple routes (`:id`) | Baseline |
| Constrained routes (`:id<\d+>`) | ~5-10% overhead |
| Complex patterns | ~20-40% overhead |
| Very complex patterns | ~50% overhead |

### Optimization Tips

1. **Use static routes when possible**
   ```php
   Router::get('/api/v1/users', $handler); // Fastest
   ```

2. **Place most specific routes first**
   ```php
   Router::get('/users/:id<\d+>', $numericHandler);
   Router::get('/users/:username<[a-z]+>', $usernameHandler);
   ```

3. **Use shortcuts instead of full regex**
   ```php
   Router::get('/users/:id<int>', $handler); // Better
   Router::get('/users/:id<\d+>', $handler); // Good
   ```

## Migration Guide

### From Basic Routes

The new regex routing is fully backward compatible:

```php
// Old style - still works!
Router::get('/users/:id', $handler);

// New style with constraints
Router::get('/users/:id<\d+>', $handler);
```

### From Manual Validation

Before:
```php
Router::get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    
    // Manual validation
    if (!is_numeric($id)) {
        return $res->status(400)->json(['error' => 'Invalid ID']);
    }
    
    // ... rest of handler
});
```

After:
```php
Router::get('/users/:id<\d+>', function($req, $res) {
    $id = $req->param('id'); // Already validated!
    // ... rest of handler
});
```

### Route Groups

Constraints work seamlessly with route groups:

```php
Router::group('/api/v1', function() {
    Router::get('/users/:id<\d+>', $handler);
    Router::get('/posts/:year<\d{4}>/:slug<slug>', $handler);
    Router::get('/files/:name<[\w-]+>.:ext<jpg|png|gif>', $handler);
});
```

## Examples

### REST API with Constraints

```php
// User routes with different parameter types
Router::get('/users/:id<\d+>', $getUserById);
Router::get('/users/:username<[a-z][a-z0-9_]{2,19}>', $getUserByUsername);
Router::get('/users/:uuid<uuid>', $getUserByUuid);

// Post routes with date archives
Router::get('/posts/:year<year>', $getPostsByYear);
Router::get('/posts/:year<year>/:month<month>', $getPostsByMonth);
Router::get('/posts/:year<year>/:month<month>/:day<day>', $getPostsByDay);
Router::get('/posts/:slug<slug>', $getPostBySlug);

// File handling
Router::get('/uploads/:year<year>/:month<month>/:file<[\w-]+>.:ext<jpg|png|pdf>', $getFile);
```

### Multi-format API Endpoints

```php
// Support multiple response formats
Router::get('/api/users/:id<\d+>.:format<json|xml|csv>', function($req, $res) {
    $userId = $req->param('id');
    $format = $req->param('format');
    
    $userData = getUserData($userId);
    
    switch ($format) {
        case 'json':
            return $res->json($userData);
        case 'xml':
            return $res->xml($userData);
        case 'csv':
            return $res->csv($userData);
    }
});
```

## Debugging

### Check Available Shortcuts

```php
$shortcuts = RouteCache::getAvailableShortcuts();
print_r($shortcuts);
```

### Route Cache Information

```php
$debugInfo = RouteCache::getDebugInfo();
echo "Cached routes: " . $debugInfo['cache_size']['routes'] . "\n";
echo "Hit rate: " . $debugInfo['statistics']['hit_rate_percentage'] . "%\n";
```

## Conclusion

Regex routing in PivotPHP provides a powerful way to validate and constrain route parameters at the routing level, improving both security and performance by failing fast on invalid requests. The feature is designed to be intuitive, secure, and performant, with full backward compatibility for existing applications.