# Changelog

All notable changes to the PivotPHP Framework will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.2] - 2025-07-11

### 🎯 **Consolidation Edition**

#### Added
- **Structured Middleware Architecture**: Complete reorganization of middleware system
  - `src/Middleware/Security/`: Authentication, CSRF, XSS, Security Headers
  - `src/Middleware/Performance/`: Cache, Rate Limiting
  - `src/Middleware/Http/`: CORS, Error Handling
  - **12 Compatibility Aliases**: 100% backward compatibility maintained
  
- **Enhanced Code Quality**:
  - **PHPStan Level 9**: Zero errors, maximum static analysis
  - **100% Test Success**: 430/430 tests passing
  - **PSR-12 Compliance**: Complete coding standards adherence
  - **Performance Maintained**: 48,323 ops/sec average maintained

#### Changed
- **Consolidated Duplicate Classes**: Eliminated 100% of critical duplications
  - Removed `Support/Arr.php` → Migrated to `Utils/Arr.php`
  - Consolidated `PerformanceMonitor` → Single implementation in `Performance/`
  - Unified `DynamicPool` → `DynamicPoolManager` in `Http/Pool/`
  - **3.1% Code Reduction**: 1,006 lines removed (30,627 → 29,621)
  - **2.5% File Reduction**: 3 files removed (121 → 118)

- **Improved Architecture**:
  - **Standardized Namespaces**: Consistent naming across all components
  - **Logical Organization**: Components grouped by responsibility
  - **Enhanced Maintainability**: Cleaner, more navigable codebase
  - **Developer Experience**: Intuitive structure for faster development

#### Fixed
- **DynamicPoolManager**: Complete method compatibility with original `DynamicPool`
- **Arr Utility**: Added missing `shuffle()` method with key preservation
- **Pool Statistics**: Enhanced `getStats()` method with comprehensive metrics
- **Memory Management**: Improved memory tracking and statistics

#### Removed
- **Critical Duplications**: All 5 identified duplications eliminated
- **Deprecated Code**: Removed obsolete `Support/Arr.php` wrapper
- **Redundant Implementations**: Consolidated multiple duplicate classes

#### Migration
- **Automatic Compatibility**: All existing code continues working
- **Recommended Updates**: New namespace structure for better organization
- **Migration Scripts**: Available for gradual transition to new structure
- **Zero Breaking Changes**: 100% backward compatibility preserved

## [1.1.1] - 2025-07-10

### 🚀 **JSON Optimization Edition**

#### Added
- **High-Performance JSON Buffer Pooling System**: Revolutionary JSON processing optimization
  - `JsonBuffer`: Optimized buffer class for JSON operations with automatic expansion
  - `JsonBufferPool`: Intelligent pooling system with buffer reuse and size categorization
  - **Automatic Integration**: `Response::json()` now uses pooling transparently for optimal performance
  - **Smart Detection**: Automatically activates pooling for arrays 10+ elements, objects 5+ properties, strings >1KB
  - **Graceful Fallback**: Small datasets use traditional `json_encode()` for best performance
  - **Public Constants**: All size estimation and threshold constants are now publicly accessible for advanced usage and testing
  
- **Performance Monitoring & Statistics**:
  - Real-time pool statistics with reuse rates and efficiency metrics
  - Configurable pool sizes and buffer categories (small: 1KB, medium: 4KB, large: 16KB, xlarge: 64KB)
  - Production-ready monitoring with `JsonBufferPool::getStatistics()`
  - Performance tracking for optimization and debugging

- **Developer Experience**:
  - **Zero Breaking Changes**: All existing code continues working without modification
  - **Transparent Optimization**: Automatic activation based on data characteristics
  - **Manual Control**: Direct pool access via `JsonBufferPool::encodeWithPool()` when needed
  - **Configuration API**: Production tuning via `JsonBufferPool::configure()`
  - **Enhanced Error Handling**: Precise validation messages separating type vs range errors
  - **Type Safety**: `encodeWithPool()` now always returns string, simplifying error handling

#### Performance Improvements
- **Sustained Throughput**: 101,000+ JSON operations per second in continuous load tests
- **Memory Efficiency**: 100% buffer reuse rate in high-frequency scenarios
- **Reduced GC Pressure**: Significant reduction in garbage collection overhead
- **Scalable Architecture**: Adaptive pool sizing based on usage patterns

#### Technical Details
- **PSR-12 Compliant**: All new code follows project coding standards
- **Comprehensive Testing**: 84 JSON tests with 329+ assertions covering all functionality
- **Backward Compatible**: No changes required to existing applications
- **Production Ready**: Tested with various data sizes and load patterns
- **Centralized Constants**: All thresholds and size constants are unified to avoid duplication
- **Test Maintainability**: Tests now use constants instead of hardcoded values for better maintainability

#### Files Added
- `src/Json/Pool/JsonBuffer.php`: Core buffer implementation
- `src/Json/Pool/JsonBufferPool.php`: Pool management system
- `tests/Json/Pool/JsonBufferTest.php`: Comprehensive buffer tests
- `tests/Json/Pool/JsonBufferPoolTest.php`: Pool functionality tests
- `benchmarks/JsonPoolingBenchmark.php`: Performance validation tools

#### Files Modified
- `src/Http/Response.php`: Integrated automatic pooling in `json()` method
- Enhanced with smart detection and fallback mechanisms

#### Post-Release Improvements (July 2025)
- **Enhanced Configuration Validation**: Separated type checking from range validation for more precise error messages
- **Improved Type Safety**: `encodeWithPool()` method now has tightened return type (always returns string)
- **Public Constants Exposure**: Made all size estimation and threshold constants public for advanced usage and testing
- **Centralized Thresholds**: Unified pooling decision thresholds across Response.php and JsonBufferPool to eliminate duplication
- **Test Maintainability**: Updated all tests to use constants instead of hardcoded values
- **Documentation Updates**: 
  - Added comprehensive [Constants Reference Guide](docs/technical/json/CONSTANTS_REFERENCE.md)
  - Updated performance guide with recent improvements
  - Enhanced error handling documentation

## [1.1.0] - 2025-07-09

### 🚀 **High-Performance Edition**

> 📖 **Complete documentation:** [docs/releases/v1.1.0/](docs/releases/v1.1.0/)

#### Added
- **High-Performance Mode**: Centralized performance management with pre-configured profiles
  - `STANDARD` profile for applications <1K req/s
  - `HIGH` profile for 1K-10K req/s
  - `EXTREME` profile for >10K req/s
  - Easy one-line enablement: `HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH)`
- **Dynamic Object Pooling**: Auto-scaling pools with intelligent overflow handling
  - `DynamicPool` with automatic expansion/shrinking based on load
  - Four overflow strategies: ElasticExpansion, PriorityQueuing, GracefulFallback, SmartRecycling
  - Emergency mode for extreme load conditions
  - Pool metrics and efficiency tracking
- **Performance Middleware Suite**:
  - `LoadShedder`: Intelligent request dropping under overload (priority, random, oldest, adaptive strategies)
  - `CircuitBreaker`: Failure isolation with automatic recovery (CLOSED, OPEN, HALF_OPEN states)
  - Enhanced `RateLimiter` with burst support and priority handling
- **Memory Management System**:
  - `MemoryManager` with adaptive GC strategies
  - Automatic pool size adjustments based on memory pressure
  - Four pressure levels: LOW, MEDIUM, HIGH, CRITICAL
  - Emergency mode activation under critical conditions
- **Distributed Pool Coordination** (Extension-based):
  - `DistributedPoolManager` for multi-instance deployments
  - Built-in `NoOpCoordinator` for single-instance operation
  - Redis/etcd/Consul support via optional extensions
  - Leader election for pool rebalancing
  - Cross-instance object sharing
- **Real-Time Performance Monitoring**:
  - `PerformanceMonitor` with live metrics collection
  - Latency percentiles (P50, P90, P95, P99)
  - Throughput and error rate tracking
  - Prometheus-compatible metric export
  - Built-in alerting system
- **Console Commands**:
  - `pool:stats` for real-time pool monitoring
  - Performance metrics display
  - Health status monitoring

#### Performance Improvements
- **25x faster** Request/Response creation (2K → 50K ops/s)
- **90% reduction** in memory usage per request (100KB → 10KB)
- **90% reduction** in P99 latency (50ms → 5ms)
- **10x increase** in max throughput (5K → 50K req/s)
- **Zero downtime** during pool scaling operations

#### Documentation
- **HIGH_PERFORMANCE_GUIDE.md**: Complete usage guide with examples
- **ARCHITECTURE.md**: Technical architecture and component design
- **PERFORMANCE_TUNING.md**: Production tuning for maximum performance
- **MONITORING.md**: Monitoring setup with Prometheus/Grafana

## [1.0.1] - 2025-07-09

### 🔄 **PSR-7 Hybrid Support & Performance Optimizations**

> 📖 **See complete overview:** [docs/technical/http/](docs/technical/http/)

#### Added
- **PSR-7 Hybrid Implementation**: Request/Response classes now implement PSR-7 interfaces while maintaining Express.js API
  - `Request` implements `ServerRequestInterface` with full PSR-7 compatibility
  - `Response` implements `ResponseInterface` with full PSR-7 compatibility
  - 100% backward compatibility - existing code works without changes
  - Lazy loading for PSR-7 objects - created only when needed
  - Support for PSR-15 middleware with type hints
- **Object Pooling System**: Advanced memory optimization for high-performance scenarios
  - `Psr7Pool` class managing pools for ServerRequest, Response, Uri, and Stream objects
  - `OptimizedHttpFactory` with configurable pooling settings
  - Automatic object reuse to reduce garbage collection pressure
  - Configurable pool sizes and warm-up capabilities
  - Performance metrics and monitoring tools
- **Debug Mode Documentation**: Comprehensive guide for debugging applications
  - Environment configuration options
  - Logging and error handling best practices
  - Security considerations for debug mode
  - Performance impact analysis
- **Enhanced Documentation**: Complete PSR-7 hybrid usage guides
  - Updated Request/Response documentation with PSR-7 examples
  - Object pooling configuration and usage examples
  - Performance optimization techniques

#### Changed
- **Request Class**: Now extends PSR-7 ServerRequestInterface while maintaining Express.js methods
  - `getBody()` method renamed to `getBodyAsStdClass()` for legacy compatibility
  - Added PSR-7 methods: `getMethod()`, `getUri()`, `getHeaders()`, `getBody()`, etc.
  - `getHeaders()` renamed to `getHeadersObject()` for Express.js style (returns HeaderRequest)
  - Immutable `with*()` methods for PSR-7 compliance
  - Lazy loading implementation for performance
- **Distributed Pooling**: Now requires external extensions for coordination backends
  - Redis support moved to `pivotphp/redis-pool` extension
  - Built-in `NoOpCoordinator` for single-instance deployments
  - Automatic fallback when extensions are not available
- **Response Class**: Now extends PSR-7 ResponseInterface while maintaining Express.js methods
  - Added PSR-7 methods: `getStatusCode()`, `getHeaders()`, `getBody()`, etc.
  - Immutable `with*()` methods for PSR-7 compliance
  - Lazy loading implementation for performance
- **Factory System**: Enhanced with pooling capabilities
  - `OptimizedHttpFactory` replaces basic HTTP object creation
  - Configurable pooling for better memory management
  - Automatic object lifecycle management

#### Fixed
- **Type Safety**: Resolved PHPStan Level 9 issues with PSR-7 implementation
- **Method Conflicts**: Fixed `getBody()` method conflict between legacy and PSR-7 interfaces
- **File Handling**: Improved file upload handling with proper PSR-7 stream integration
- **Immutability**: Ensured proper immutability in PSR-7 `with*()` methods
- **Test Compatibility**: Updated test suite to work with hybrid implementation

#### Performance Improvements
- **Lazy Loading**: PSR-7 objects created only when accessed, reducing memory usage
- **Object Pooling**: Significant reduction in object creation and garbage collection
- **Optimized Factory**: Intelligent object reuse for better performance
- **Memory Efficiency**: Up to 60% reduction in memory usage for high-traffic scenarios

#### Examples
```php
// Express.js API (unchanged)
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    return $res->json(['user' => $userService->find($id)]);
});

// PSR-7 API (now supported)
$app->use(function(ServerRequestInterface $request, ResponseInterface $response, $next) {
    $method = $request->getMethod();
    $newRequest = $request->withAttribute('processed', true);
    return $next($newRequest, $response);
});

// Object pooling configuration
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'warm_up_pools' => true,
    'max_pool_size' => 100,
]);
```

## [1.0.1] - 2025-07-08

### 🆕 **Regex Route Validation Support & PSR-7 Compatibility**

> 📖 **See complete overview:** [docs/releases/FRAMEWORK_OVERVIEW_v1.0.1.md](docs/releases/FRAMEWORK_OVERVIEW_v1.0.1.md)

#### Added
- **Regex Constraints**: Advanced pattern matching for route parameters
- **Predefined Shortcuts**: Common patterns (int, slug, uuid, date, etc.)
- **Full Regex Blocks**: Complete control over route segments
- **Non-greedy Pattern Matching**: Improved regex processing
- **Backward Compatibility**: All v1.0.0 routes continue to work
- **PSR-7 Dual Version Support**: Full compatibility with both PSR-7 v1.x and v2.x
  - Automatic version detection via `Psr7VersionDetector`
  - Script to switch between versions: `scripts/switch-psr7-version.php`
  - Enables ReactPHP integration with PSR-7 v1.x
  - Maintains type safety with PSR-7 v2.x

#### Changed
- Refactored `RouteCache::compilePattern()` into 12 focused helper methods
- Improved route compilation performance with better regex handling
- Enhanced parameter extraction logic with shared helper method
- Updated documentation positioning (ideal for concept validation and studies)
- Added comprehensive documentation for regex block pattern limitations
- Created dedicated test suite for regex block validation
- Updated composer.json to support `"psr/http-message": "^1.1|^2.0"`

#### Fixed
- Route pattern compilation preserving URL-encoded characters
- Regex anchors being duplicated in full regex blocks
- Greedy regex pattern spanning multiple blocks
- PHPStan warnings about type comparisons
- PSR-12 code style violations

#### Examples
```php
// Numeric validation
$app->get('/users/:id<\\d+>', handler);

// Using shortcuts
$app->get('/posts/:slug<slug>', handler);
$app->get('/items/:uuid<uuid>', handler);

// Date validation
$app->get('/archive/:year<\\d{4}>/:month<\\d{2}>', handler);

// Full regex blocks
$app->get('/api/{^v(\\d+)$}/users', handler);
```

## [1.0.0] - 2025-07-07

### 🚀 **Initial Stable Release**

> 📖 **See complete overview:** [docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md](docs/releases/FRAMEWORK_OVERVIEW_v1.0.0.md)

#### Added
- **High-Performance Framework**: Modern PHP microframework with advanced optimizations
- **PSR Compliance**: Full PSR-7, PSR-11, PSR-12, PSR-14, PSR-15 compatibility
- **Security Suite**: Built-in CORS, CSRF, XSS protection, JWT authentication
- **Middleware System**: Flexible PSR-15 middleware pipeline with performance optimizations
- **Dependency Injection**: Advanced DI container with service providers
- **Event System**: PSR-14 compliant event dispatcher with hooks
- **Extension System**: Plugin architecture with auto-discovery
- **Performance Monitoring**: Built-in benchmarking and profiling tools
- **Developer Experience**: Hot reload, detailed logging, OpenAPI support

#### Features
- **Authentication**: JWT, Basic Auth, Bearer Token, API Key support
- **Rate Limiting**: Advanced rate limiting with multiple algorithms
- **Caching**: Multi-layer caching system with intelligent invalidation
- **Request/Response**: Full PSR-7 HTTP message implementation
- **Routing**: High-performance router with middleware support
- **Validation**: Data validation with custom rules
- **Error Handling**: Comprehensive error handling and debugging
- **Testing**: 270+ unit and integration tests

#### Performance Metrics
- **2.57M ops/sec**: CORS Headers Generation
- **2.27M ops/sec**: Response Creation
- **757K ops/sec**: Route Resolution
- **1.4K req/sec**: End-to-end throughput
- **1.2 MB**: Memory usage
- **0.71 ms**: Average latency

#### Quality Assurance
- ✅ **PHPStan Level 9**: Zero static analysis errors
- ✅ **PSR-12**: 100% code style compliance
- ✅ **270+ Tests**: Comprehensive test coverage
- ✅ **PHP 8.1+**: Modern PHP version support
- ✅ **Performance Validated**: Optimized for high-performance applications

#### Technical Stack
- **PHP**: 8.1+ with full 8.4 compatibility
- **Standards**: PSR-7, PSR-11, PSR-12, PSR-14, PSR-15
- **Testing**: PHPUnit with extensive coverage
- **Quality**: PHPStan Level 9, PHP_CodeSniffer PSR-12
- **Performance**: Optimized for high-concurrency applications

#### Documentation
- Complete API documentation
- Performance benchmarks and analysis
- Integration guides and examples
- Security best practices
- Extension development guide

---

### 📋 Release Notes

This is the first stable release of PivotPHP Framework v1.0.0. The framework has been designed from the ground up for modern PHP development with a focus on:

1. **Performance**: Optimized for high-throughput applications
2. **Security**: Built-in protection against common vulnerabilities  
3. **Developer Experience**: Modern tooling and comprehensive documentation
4. **Extensibility**: Plugin system for custom functionality
5. **Standards Compliance**: Following PHP-FIG recommendations

### 🔄 Future Roadmap

The v1.0.0 release establishes a stable foundation. Future updates will focus on:
- Additional middleware components
- Enhanced performance optimizations
- Extended documentation and examples
- Community contributions and feedback integration

### 📞 Support

For questions, issues, or contributions:
- **GitHub**: [https://github.com/PivotPHP/pivotphp-core](https://github.com/PivotPHP/pivotphp-core)
- **Documentation**: [docs/](docs/)
- **Examples**: [examples/](examples/)
- **Benchmarks**: [benchmarks/](benchmarks/)

---

**Current Version**: v1.0.1  
**Release Date**: July 9, 2025  
**Status**: Production-ready with PSR-7 hybrid support  
**Minimum PHP**: 8.1