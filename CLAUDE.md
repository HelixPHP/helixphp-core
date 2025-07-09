# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

PivotPHP Core is a high-performance PHP microframework inspired by Express.js, designed for building APIs and web applications. Current version: 1.1.0 (High-Performance Edition).

## Essential Commands

### Development Workflow
```bash
# Run comprehensive validation (includes all checks)
./scripts/validate_all.sh

# Quality checks
composer quality:check    # Run all quality checks
composer phpstan         # Static analysis (Level 9)
composer cs:check        # PSR-12 code style check
composer cs:fix          # Auto-fix code style issues
composer audit          # Check for security vulnerabilities

# Testing
composer test                    # Run all tests
composer test:security          # Security-specific tests
composer test:auth             # Authentication tests
composer benchmark             # Performance benchmarks

# Run a single test file
vendor/bin/phpunit tests/Core/ApplicationTest.php

# Run tests with specific group
vendor/bin/phpunit --group stress
vendor/bin/phpunit --exclude-group stress,integration

# Pre-commit and release
./scripts/pre-commit             # Run pre-commit validations
./scripts/prepare_release.sh 1.1.0  # Prepare release for version 1.1.0
./scripts/release.sh            # Create release after preparation
```

### Running Examples
```bash
composer examples:basic        # Basic framework usage
composer examples:auth        # Authentication example
composer examples:middleware  # Middleware example
```

### v1.1.0 High-Performance Features
```php
// Enable high-performance mode
use PivotPHP\Core\Performance\HighPerformanceMode;
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Console commands for monitoring
php bin/console pool:stats     # Real-time pool monitoring
```

## Code Architecture

### Core Framework Structure
- **Service Provider Pattern**: All major components are registered via service providers in `src/Providers/`
- **PSR Standards**: Strict PSR-7 (HTTP messages), PSR-15 (middleware), PSR-12 (coding style) compliance
- **Container**: Dependency injection container at the heart of the framework (`src/Core/Container.php`)
- **Event-Driven**: Event dispatcher with hooks system for extensibility

### Key Components
1. **Application Core** (`src/Core/Application.php`): Main application class that bootstraps the framework
   - Version constant: `Application::VERSION`
   - Middleware aliases mapping for v1.1.0 features
   
2. **Router** (`src/Routing/Router.php`): High-performance routing with middleware support
   - Supports regex constraints: `/users/:id<\d+>`
   - Predefined shortcuts: `slug`, `uuid`, `date`, etc.
   
3. **Middleware Pipeline** (`src/Middleware/`): PSR-15 compliant middleware system
   - v1.1.0 additions: LoadShedder, CircuitBreaker
   
4. **HTTP Layer** (`src/Http/`): PSR-7 hybrid implementation
   - Express.js style API with PSR-7 compliance
   - Object pooling via `Psr7Pool` and `OptimizedHttpFactory`
   
5. **v1.1.0 Performance Components**:
   - `DynamicPool`: Auto-scaling object pools
   - `MemoryManager`: Adaptive memory management
   - `PerformanceMonitor`: Real-time metrics
   - `DistributedPoolManager`: Multi-instance coordination

### Request/Response Hybrid Design
The framework uses a hybrid approach for PSR-7 compatibility:
- `Request` class implements `ServerRequestInterface` while maintaining Express.js methods
- Legacy `getBody()` renamed to `getBodyAsStdClass()` for backward compatibility
- PSR-7 objects are lazy-loaded for performance

### Testing Approach
- Tests organized by domain in `tests/` directory
- Each major component has its own test suite
- Integration tests verify component interaction
- Use PHPUnit assertions and follow existing test patterns
- v1.1.0 tests in `tests/Integration/V11ComponentsTest.php` and `tests/Stress/`

### Code Style Requirements
- PHP 8.1+ features are used throughout
- Strict typing is enforced
- PHPStan Level 9 must pass
- PSR-12 coding standard via PHP_CodeSniffer
- All new code must include proper type declarations

### Performance Considerations
- Framework optimized for high throughput (2.57M ops/sec for CORS)
- v1.1.0 achieves 25x faster Request/Response creation with pooling
- Benchmark any performance-critical changes using `composer benchmark`
- Avoid unnecessary object creation in hot paths
- Use lazy loading for optional dependencies

## Development Workflow

1. Before committing, run `./scripts/pre-commit` or `./scripts/validate_all.sh`
2. All tests must pass before pushing changes
3. Static analysis must pass at Level 9
4. Code style must comply with PSR-12
5. For releases, use `./scripts/prepare_release.sh` followed by `./scripts/release.sh`

## Current Version Status

- **Current Version**: 1.1.0 (High-Performance Edition)
- **Previous Stable**: 1.0.1 (PSR-7 Hybrid Support)
- **Tests Status**: 315/332 passing (95% success rate)
- **New Features**: High-performance mode, dynamic pooling, circuit breaker, load shedding

## Important Notes

- The framework prioritizes performance, security, and developer experience
- All HTTP components are PSR-7/PSR-15 compliant
- Service providers are the primary extension mechanism
- The event system allows for deep customization without modifying core code
- Documentation updates should be made in the `/docs` directory when adding features
- v1.1.0 features are opt-in and don't affect default behavior