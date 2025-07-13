# PivotPHP Core v1.1.3 Documentation

Welcome to the complete documentation for **PivotPHP Core v1.1.3** - a high-performance, lightweight PHP microframework inspired by Express.js, designed for building APIs and web applications with exceptional speed and simplicity.

## 🚀 Quick Navigation

### Essential Guides
- **[Quick Start](quick-start.md)** - Get running in 5 minutes
- **[API Reference](API_REFERENCE.md)** - Complete API documentation
- **[Migration Guide](MIGRATION_GUIDE.md)** - Upgrading from previous versions

### Core Guides
- **[Architecture Guide](guides/architecture.md)** - v1.1.3 architecture overview
- **[Performance Guide](guides/performance.md)** - Optimization and benchmarks  
- **[Testing Guide](guides/testing.md)** - Testing strategies and examples

### Reference Materials
- **[Examples Catalog](reference/examples.md)** - Complete examples collection
- **[Middleware Reference](reference/middleware.md)** - All available middleware
- **[Routing Reference](reference/routing.md)** - Routing patterns and constraints
- **[Configuration Reference](reference/configuration.md)** - Framework configuration

## 📚 Learning Paths

### 👶 **Beginner Path** (New to PivotPHP)
1. [Quick Start](quick-start.md) - Basic setup and hello world
2. [Basic Usage Examples](implementations/usage_basic.md) - Simple API creation
3. [Routing Guide](technical/routing/SYNTAX_GUIDE.md) - URL routing patterns
4. [Request/Response](technical/http/README.md) - Handling HTTP

### 🏃 **Intermediate Path** (Building Production APIs)
1. [Middleware Usage](implementations/usage_with_middleware.md) - Security and performance middleware
2. [Authentication](technical/authentication/README.md) - JWT and API key auth
3. [Testing](guides/testing.md) - Unit and integration testing
4. [Performance Optimization](guides/performance.md) - Object pooling and optimization

### 🚀 **Advanced Path** (Framework Extension)
1. [Architecture Guide](guides/architecture.md) - Framework internals
2. [Custom Middleware](implementations/usage_with_custom_middleware.md) - Building custom components
3. [Service Providers](technical/providers/README.md) - Dependency injection
4. [Extensions](technical/extensions/README.md) - Framework extensions

## ✨ v1.1.3 Highlights

### 🎯 **Array Callable Support**
Full PHP 8.4+ compatibility with array callable route handlers:
```php
// NEW: Array callable syntax
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [$controller, 'store']);
```

### ⚡ **Performance Revolution**  
- **+116% framework performance improvement** (20,400 → 44,092 ops/sec)
- **100% object pool reuse rate** (was 0%)
- **Multi-PHP validation** across PHP 8.1-8.4

### 🏗️ **Architectural Excellence**
- **Organized middleware structure** by responsibility
- **Over-engineering elimination** following ARCHITECTURAL_GUIDELINES
- **100% backward compatibility** via automatic aliases

### 🧪 **Quality Assurance**
- **PHPStan Level 9** across all PHP versions
- **684+ tests passing** with comprehensive coverage
- **Zero breaking changes** for existing applications

## 🔧 Framework Status

- **Current Version**: v1.1.3 (Performance Optimization & Array Callables Edition)
- **PHP Requirements**: 8.1+ with strict typing
- **Production Ready**: Enterprise-grade quality with type safety
- **Community**: [Discord](https://discord.gg/DMtxsP7z) | [GitHub](https://github.com/PivotPHP/pivotphp-core)

## 🧩 Ecosystem

### Official Extensions
- **[Cycle ORM Extension](https://github.com/PivotPHP/pivotphp-cycle-orm)** - Database integration
- **[ReactPHP Extension](https://github.com/PivotPHP/pivotphp-reactphp)** - Async runtime

### Community Resources
- **[Benchmarks Repository](https://github.com/PivotPHP/pivotphp-benchmarks)** - Performance testing
- **[Examples Collection](examples/)** - Practical usage examples
- **[Community Discord](https://discord.gg/DMtxsP7z)** - Support and discussion

## 📖 Technical Documentation

### Core Components
- **[Application](technical/application.md)** - Framework bootstrap and lifecycle
- **[HTTP Layer](technical/http/README.md)** - Request/response handling
- **[Routing](technical/routing/router.md)** - URL routing and parameters
- **[Middleware](technical/middleware/README.md)** - Request/response pipeline

### Advanced Topics
- **[Authentication](technical/authentication/README.md)** - Multi-method authentication
- **[Performance](technical/performance/)** - Object pooling and optimization
- **[JSON Optimization](technical/json/README.md)** - Buffer pooling system
- **[PSR Compatibility](technical/compatibility/)** - PSR-7/PSR-15 compliance

## 🤝 Contributing

Interested in contributing to PivotPHP Core? See our [Contributing Guide](contributing/README.md) for:
- Development setup
- Code style requirements  
- Testing procedures
- Pull request process

## 📄 License

PivotPHP Core is open-source software licensed under the [MIT License](../LICENSE).

---

*Built with ❤️ for the PHP community - Combining Express.js simplicity with PHP power*