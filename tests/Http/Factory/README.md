# OptimizedHttpFactory Tests

## Overview

This directory contains comprehensive tests for the `OptimizedHttpFactory` class, covering all aspects of the factory's functionality, performance, and integration with the `Psr7Pool` system.

## Test Files

### OptimizedHttpFactoryTest.php
Main test suite covering core factory functionality:

- **Initialization Tests**: Factory initialization with default and custom configurations
- **Object Creation Tests**: Creation of hybrid Request/Response and PSR-7 objects
- **Pooling Management**: Enable/disable pooling, pool state management
- **Configuration Management**: Config updates, persistence, and retrieval
- **Metrics and Statistics**: Performance metrics, pool statistics, recommendations
- **Object Return**: Manual object return to pool
- **Utility Methods**: Pool clearing, warm-up, reset functionality
- **Edge Cases**: Error handling, memory efficiency, concurrent access

#### Key Test Cases:
- `testInitialization()` - Basic factory initialization
- `testCreateRequest()` - Hybrid Request creation
- `testCreateResponse()` - Hybrid Response creation
- `testCreateServerRequest()` - PSR-7 ServerRequest creation
- `testCreatePsr7Response()` - PSR-7 Response creation
- `testCreateStream()` - Stream creation with different content types
- `testCreateUri()` - URI creation with various formats
- `testPoolingEnabled()` / `testPoolingDisabled()` - Pooling behavior
- `testPerformanceMetrics()` - Metrics collection and reporting
- `testMemoryEfficiencyWithPooling()` - Memory usage validation
- `testComprehensiveWorkflow()` - End-to-end functionality test

### OptimizedHttpFactoryIntegrationTest.php
Integration tests focusing on factory interaction with `Psr7Pool`:

- **Pool Integration**: Factory-pool interaction and object reuse
- **Metrics Integration**: Performance metrics correlation between factory and pool
- **Configuration Integration**: Factory configuration effects on pool behavior
- **Warm-up Integration**: Pool warm-up through factory initialization
- **Stress Testing**: High-load scenarios with pool limits
- **Mixed Object Types**: Hybrid and PSR-7 object handling
- **Reset Integration**: Factory reset effects on pool state

#### Key Integration Test Cases:
- `testFactoryPoolIntegration()` - Factory-pool object reuse verification
- `testFactoryPerformanceMetricsIntegration()` - Metrics correlation
- `testFactoryWithPoolDisabled()` - Factory behavior without pooling
- `testFactoryWarmUpIntegration()` - Pool warm-up verification
- `testFactoryStressWithPoolLimits()` - High-load testing
- `testFactoryResetWithPoolIntegration()` - Reset functionality integration
- `testFactoryWithMixedObjectTypes()` - Mixed object type handling

## Test Groups

The tests are organized into the following PHPUnit groups:

- `@group factories` - All factory-related tests
- `@group pools` - Pool integration tests
- `@group http` - HTTP layer tests
- `@group performance` - Performance-related tests
- `@group integration` - Integration tests

## Running Tests

```bash
# Run all factory tests
vendor/bin/phpunit tests/Http/Factory/

# Run specific test file
vendor/bin/phpunit tests/Http/Factory/OptimizedHttpFactoryTest.php

# Run by group
vendor/bin/phpunit --group factories
vendor/bin/phpunit --group pools

# Run with coverage (requires Xdebug)
XDEBUG_MODE=coverage vendor/bin/phpunit tests/Http/Factory/ --coverage-text
```

## Test Coverage

The test suite provides comprehensive coverage of:

### Core Functionality (100%)
- All public methods of `OptimizedHttpFactory`
- Configuration management
- Object creation for all supported types
- Pool management operations

### Edge Cases and Error Handling
- Invalid configurations
- File system errors (stream from file)
- Memory efficiency under stress
- Concurrent object creation
- Pool limits and overflow scenarios

### Performance and Metrics
- Pool statistics collection
- Performance metrics generation
- Recommendation system
- Memory usage tracking
- Object reuse efficiency

### Integration Scenarios
- Factory-pool interaction
- Configuration persistence
- Warm-up behavior
- Reset functionality
- Mixed object type handling

## Key Insights from Tests

### Performance Optimizations Verified
1. **Object Pooling**: Tests confirm that object reuse reduces memory allocation
2. **Lazy Initialization**: Factory initializes only when needed
3. **Configuration Caching**: Config changes are properly persisted
4. **Pool Limits**: Respects maximum pool sizes to prevent memory leaks

### Architecture Validation
1. **PSR-7 Compliance**: All created objects implement proper PSR-7 interfaces
2. **Hybrid Support**: Factory creates both hybrid and pure PSR-7 objects
3. **Pool Integration**: Seamless integration with `Psr7Pool` system
4. **Metrics Collection**: Comprehensive statistics for performance monitoring

### Error Handling
1. **Graceful Degradation**: Factory works even with pooling disabled
2. **Invalid Input**: Proper handling of invalid configurations
3. **Resource Cleanup**: Proper cleanup on reset and pool clearing
4. **Memory Management**: No memory leaks under stress conditions

## Test Maintenance

When modifying `OptimizedHttpFactory`:

1. **Update Tests**: Ensure all new public methods have corresponding tests
2. **Configuration Changes**: Update configuration tests if config structure changes
3. **Pool Integration**: Verify pool interaction tests if pool behavior changes
4. **Performance Impact**: Run performance tests to validate optimizations
5. **Backward Compatibility**: Ensure existing functionality remains intact

## Dependencies

The tests depend on:
- `PHPUnit 10.5+`
- `PivotPHP\Core\Http\Factory\OptimizedHttpFactory`
- `PivotPHP\Core\Http\Pool\Psr7Pool`
- `PivotPHP\Core\Http\Request` and `Response`
- PSR-7 interfaces

## Notes

- Tests use `setUp()` and `tearDown()` methods to ensure clean state
- Factory reset is called between tests to prevent state pollution
- Integration tests clear both factory and pool state
- Memory tests use garbage collection to ensure accurate measurements
- File-based tests use temporary files that are properly cleaned up