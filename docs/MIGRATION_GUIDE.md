# PivotPHP Core - Migration Guide

This guide helps you migrate between versions of PivotPHP Core, covering breaking changes, new features, and best practices for upgrading.

## Current Version: v1.1.3-dev

### Migration Path: v1.1.2 → v1.1.3-dev

#### ✅ Zero Breaking Changes
This is a **seamless upgrade** with full backward compatibility.

```php
// All existing code continues to work unchanged
$app = new Application();
$app->get('/', function($req, $res) {
    return $res->json(['message' => 'Works exactly the same']);
});
$app->run();
```

#### 🆕 New Features Available
- **Enhanced Examples**: 15 production-ready examples
- **Improved Documentation**: Complete API reference
- **Better Error Messages**: More precise validation errors
- **Configuration Fixes**: Robust environment variable handling

#### 🔧 Optional Improvements

**Route Handler Syntax Clarification:**
```php
// ❌ This was never supported (documentation error)
$app->get('/users', 'UserController@index'); // TypeError!

// ✅ Use this instead (always worked)
$app->get('/users', [UserController::class, 'index']);
```

**Updated autoload paths for examples:**
```php
// New examples use correct path structure
require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';
```

### Migration Path: v1.1.1 → v1.1.2 → v1.1.3

If upgrading from v1.1.1, first migrate to v1.1.2, then to v1.1.3.

#### From v1.1.1 to v1.1.2
```php
// No code changes required - automatic compatibility
// JSON pooling continues to work exactly the same
$response->json($data); // Still automatically optimized
```

#### From v1.1.2 to v1.1.3
```php
// No code changes required
// All optimizations and features remain the same
```

## Migration Path: v1.1.0 → v1.1.3

### ✅ Compatibility Maintained
All v1.1.0 code works without changes in v1.1.3.

#### High-Performance Mode
```php
// v1.1.0 code continues to work
use PivotPHP\Core\Performance\HighPerformanceMode;

HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
$status = HighPerformanceMode::getStatus();
```

#### 🆕 Additional Features Since v1.1.0
- **JSON Buffer Pooling**: Automatic performance boost (v1.1.1)
- **Enhanced Error Handling**: Better validation messages (v1.1.1+)
- **Complete Examples**: Production-ready code samples (v1.1.3)

## Migration Path: v1.0.x → v1.1.3

### ⚠️ Some Breaking Changes from v1.0.x

#### Route Handler Format
```php
// v1.0.x - This may have worked in early versions
$app->get('/users', 'UserController@index');

// v1.1.x - Use this format
$app->get('/users', [UserController::class, 'index']);
```

#### Container Integration
```php
// v1.0.x - Basic container
$app->bind('service', $implementation);

// v1.1.x - Enhanced container with auto-resolution
$app->bind('service', $implementation);
$app->singleton('cache', CacheService::class);
```

#### Performance Features
```php
// v1.0.x - Basic framework
$app->get('/', $handler);

// v1.1.x - With performance optimizations
$app->get('/', $handler); // Automatically faster with pooling

// Optional: Enable high-performance mode
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
```

## Feature Availability by Version

| Feature | v1.0.x | v1.1.0 | v1.1.1 | v1.1.2 | v1.1.3 |
|---------|--------|--------|--------|--------|--------|
| **Express.js API** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **PSR Compliance** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Basic Routing** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Middleware System** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **High-Performance Mode** | ❌ | ✅ | ✅ | ✅ | ✅ |
| **Object Pooling** | ❌ | ✅ | ✅ | ✅ | ✅ |
| **JSON Buffer Pooling** | ❌ | ❌ | ✅ | ✅ | ✅ |
| **Enhanced Error Handling** | ❌ | ❌ | ✅ | ✅ | ✅ |
| **Code Consolidation** | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Complete Examples** | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Full Documentation** | ❌ | ❌ | ❌ | ❌ | ✅ |

## Performance Migration Guide

### Automatic Optimizations

#### JSON Operations
```php
// v1.0.x - Standard performance
$response->json($data);

// v1.1.1+ - Automatically optimized
$response->json($data); // Uses pooling for large datasets
```

#### Request/Response Objects
```php
// v1.0.x - Standard object creation
$request = new Request();

// v1.1.0+ - Automatically pooled
$request = new Request(); // Reused from pool when possible
```

### Manual Optimizations

#### Enable High-Performance Mode
```php
// Add to your bootstrap code
use PivotPHP\Core\Performance\HighPerformanceMode;

HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
```

#### Configure JSON Pooling
```php
// Optional: Tune for your workload
use PivotPHP\Core\Json\Pool\JsonBufferPool;

JsonBufferPool::configure([
    'max_pool_size' => 200,
    'default_capacity' => 8192
]);
```

## Configuration Migration

### Environment Variables
```php
// v1.0.x - Basic config
return [
    'debug' => $_ENV['APP_DEBUG'] ?? false
];

// v1.1.3 - Robust config (automatically applied)
return [
    'debug' => $_ENV['APP_DEBUG'] ?? (($_ENV['APP_ENV'] ?? 'production') === 'development' ? true : false)
];
```

### Autoloader Paths
```php
// Old project structure
require_once 'vendor/autoload.php';

// New structure with examples
require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';
```

## Testing Migration

### Test Structure
```php
// v1.0.x - Basic tests
class BasicTest extends TestCase {
    public function testRoute() {
        // Basic assertions
    }
}

// v1.1.3 - Enhanced testing with constants
class EnhancedTest extends TestCase {
    private const TEST_DATA = ['key' => 'value'];
    private const EXPECTED_STATUS = 200;
    
    public function testRoute() {
        // Tests using constants instead of hardcoded values
    }
}
```

## Best Practices for Migration

### 1. Gradual Upgrade
```php
// Step 1: Upgrade to latest v1.1.x
composer require pivotphp/core:^1.1.3

// Step 2: Run tests to ensure compatibility
./vendor/bin/phpunit

// Step 3: Enable new features gradually
```

### 2. Performance Monitoring
```php
// Add monitoring for new features
$stats = JsonBufferPool::getStatistics();
$performanceStatus = HighPerformanceMode::getStatus();

// Log performance metrics
log_info('JSON Pool Reuse Rate: ' . $stats['reuse_rate'] . '%');
log_info('High Performance Enabled: ' . ($performanceStatus['enabled'] ? 'Yes' : 'No'));
```

### 3. Code Review Checklist
- [ ] Replace `Controller@method` syntax with `[Controller::class, 'method']`
- [ ] Update autoloader paths if using examples
- [ ] Enable high-performance mode for production
- [ ] Add performance monitoring
- [ ] Update documentation references

## Troubleshooting Migration Issues

### Common Issues

#### 1. Route Handler Type Errors
```php
// Problem: TypeError on route handlers
$app->get('/users', 'UserController@index'); // ❌

// Solution: Use array callable format
$app->get('/users', [UserController::class, 'index']); // ✅
```

#### 2. Autoloader Issues
```php
// Problem: Class not found errors
require_once 'wrong/path/autoload.php'; // ❌

// Solution: Use correct path
require_once __DIR__ . '/vendor/autoload.php'; // ✅
```

#### 3. Performance Regression
```php
// Check if optimizations are enabled
$jsonStats = JsonBufferPool::getStatistics();
$hpStatus = HighPerformanceMode::getStatus();

if (!$hpStatus['enabled']) {
    HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
}
```

### Getting Help

If you encounter issues during migration:

1. **Check Examples**: Look at the 15 working examples in `/examples`
2. **API Reference**: Consult the complete API reference
3. **GitHub Issues**: Report issues at https://github.com/PivotPHP/pivotphp-core/issues
4. **Discord Community**: Join https://discord.gg/DMtxsP7z

## Version-Specific Notes

### v1.1.3-dev Notes
- **Focus**: Examples and documentation
- **Stability**: Production-ready core with development ecosystem
- **Performance**: All v1.1.1 and v1.1.0 optimizations included
- **Compatibility**: 100% backward compatible

### v1.1.2 Notes
- **Focus**: Code consolidation and organization
- **Breaking Changes**: None
- **Performance**: Maintained all previous optimizations
- **Quality**: PHPStan Level 9, PSR-12 compliance

### v1.1.1 Notes
- **Focus**: JSON optimization system
- **Breaking Changes**: None
- **Performance**: Dramatic improvement for JSON operations
- **Automatic**: Zero configuration required

### v1.1.0 Notes
- **Focus**: High-performance features
- **Breaking Changes**: None
- **Performance**: Object pooling, memory management
- **Configuration**: Optional performance profiles

## Migration Timeline Recommendations

### Immediate (Same Day)
- Upgrade to v1.1.3
- Run existing tests
- Verify basic functionality

### Within 1 Week
- Enable high-performance mode in production
- Update route handler syntax if needed
- Add performance monitoring

### Within 1 Month
- Review and implement examples relevant to your use case
- Optimize JSON pooling configuration for your workload
- Update documentation and deployment procedures

---

**Need Help?** Join our Discord community at https://discord.gg/DMtxsP7z or open an issue on GitHub for assistance with migration.