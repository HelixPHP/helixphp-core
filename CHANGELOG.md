# Changelog

All notable changes to the PivotPHP Framework will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-07-08

### 🆕 **Regex Route Validation Support**

> 📖 **See complete overview:** [docs/releases/FRAMEWORK_OVERVIEW_v1.0.1.md](docs/releases/FRAMEWORK_OVERVIEW_v1.0.1.md)

#### Added
- **Regex Constraints**: Advanced pattern matching for route parameters
- **Predefined Shortcuts**: Common patterns (int, slug, uuid, date, etc.)
- **Full Regex Blocks**: Complete control over route segments
- **Non-greedy Pattern Matching**: Improved regex processing
- **Backward Compatibility**: All v1.0.0 routes continue to work

#### Changed
- Refactored `RouteCache::compilePattern()` into 12 focused helper methods
- Improved route compilation performance with better regex handling
- Enhanced parameter extraction logic with shared helper method
- Updated documentation positioning (ideal for concept validation and studies)

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
**Release Date**: July 8, 2025  
**Status**: Ideal for concept validation and studies  
**Minimum PHP**: 8.1