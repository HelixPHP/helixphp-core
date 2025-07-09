# PivotPHP v1.1.0 High-Performance Guide

## Overview

PivotPHP v1.1.0 introduces enterprise-grade high-performance features designed for APIs that demand extreme throughput, low latency, and resilience under stress. This guide covers all new components and their usage.

## Table of Contents

1. [Quick Start](#quick-start)
2. [High-Performance Mode](#high-performance-mode)
3. [Dynamic Object Pooling](#dynamic-object-pooling)
4. [Overflow Strategies](#overflow-strategies)
5. [Performance Middleware](#performance-middleware)
6. [Memory Management](#memory-management)
7. [Distributed Pools](#distributed-pools)
8. [Performance Monitoring](#performance-monitoring)
9. [Best Practices](#best-practices)
10. [Benchmarks](#benchmarks)

## Quick Start

Enable high-performance mode with a single line:

```php
use PivotPHP\Core\Performance\HighPerformanceMode;

// Enable with pre-configured profile
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Your application automatically benefits from:
// - Object pooling
// - Load shedding
// - Circuit breakers
// - Memory optimization
// - Performance monitoring
```

## High-Performance Mode

### Available Profiles

```php
// Standard - Balanced performance and resource usage
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_STANDARD);

// High - Optimized for high throughput
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Extreme - Maximum performance, higher resource usage
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
```

### Custom Configuration

```php
HighPerformanceMode::enable([
    'pool' => [
        'enabled' => true,
        'initial_size' => 100,
        'max_size' => 1000,
        'emergency_limit' => 2000,
    ],
    'middleware' => [
        'load_shedder' => true,
        'circuit_breaker' => true,
        'rate_limiter' => false,
    ],
    'monitoring' => [
        'enabled' => true,
        'sample_rate' => 0.1, // 10% sampling
    ],
    'memory' => [
        'gc_strategy' => 'aggressive',
        'pool_adjustments' => true,
    ],
]);
```

## Dynamic Object Pooling

### How It Works

The DynamicPool automatically manages object lifecycles:

```php
use PivotPHP\Core\Http\Pool\DynamicPool;

$pool = new DynamicPool([
    'initial_size' => 50,
    'max_size' => 500,
    'emergency_limit' => 1000,
    'auto_scale' => true,
    'scale_threshold' => 0.8,  // Scale up at 80% usage
    'shrink_threshold' => 0.2, // Scale down at 20% usage
]);

// Pool automatically:
// - Expands when demand increases
// - Shrinks during low usage
// - Activates emergency mode under extreme load
```

### Manual Pool Usage

```php
// Borrow an object
$request = $pool->borrow('request', ['method' => 'GET', 'uri' => '/api']);

// Use the object
processRequest($request);

// Return to pool for reuse
$pool->return('request', $request);

// Check pool statistics
$stats = $pool->getStats();
echo "Pool efficiency: " . $stats['metrics']['efficiency'] . "%\n";
```

## Overflow Strategies

### Elastic Expansion

Temporarily allows pool to exceed normal limits:

```php
// Configured automatically in high-performance mode
// Manual configuration:
$pool = new DynamicPool([
    'overflow_strategy' => 'elastic',
    'emergency_limit' => 2000, // 2x normal max
]);
```

### Priority Queuing

Prioritizes important requests during overflow:

```php
// Set request priority
$request->headers['X-Priority'] = 'high'; // high, normal, low

// High-priority requests get pool objects first
```

### Graceful Fallback

Creates temporary objects when pools are exhausted:

```php
// Automatic in high-performance mode
// Objects are created on-demand and destroyed after use
```

### Smart Recycling

Intelligently recycles objects based on age and usage:

```php
// Objects are automatically recycled based on:
// - Time since creation
// - Number of uses
// - Memory pressure
```

## Performance Middleware

### Load Shedder

Protects against overload by intelligently dropping requests:

```php
$app->middleware('load-shedder', [
    'threshold' => 0.8,        // Start shedding at 80% load
    'strategy' => 'adaptive',  // adaptive, priority, random, oldest
    'check_interval' => 1,     // Check every second
    'min_success_rate' => 0.5, // Keep 50% success rate minimum
]);

// Adaptive strategy considers:
// - Request priority
// - Current system load
// - Historical patterns
```

### Circuit Breaker

Prevents cascade failures:

```php
$app->middleware('circuit-breaker', [
    'failure_threshold' => 50,    // Failures per minute
    'success_threshold' => 10,    // Successes to close
    'timeout' => 30,             // Seconds before retry
    'half_open_requests' => 10,  // Test requests in half-open
]);

// Circuit states:
// - Closed: Normal operation
// - Open: Reject requests (service is failing)
// - Half-Open: Testing recovery
```

### Rate Limiter (Enhanced)

```php
$app->middleware('rate-limiter', [
    'max_requests' => 1000,
    'window' => 60,           // Per minute
    'burst_size' => 50,       // Allow bursts
    'key_by' => 'ip',        // ip, user, api_key
]);
```

## Memory Management

### Adaptive Memory Manager

Automatically adjusts behavior based on memory pressure:

```php
use PivotPHP\Core\Memory\MemoryManager;

$memoryManager = new MemoryManager([
    'gc_strategy' => MemoryManager::STRATEGY_ADAPTIVE,
    'gc_threshold' => 0.7,      // GC at 70% memory
    'emergency_gc' => 0.9,      // Emergency at 90%
]);

// Memory pressure levels trigger different behaviors:
// - LOW: Expand pools, relaxed GC
// - MEDIUM: Maintain pools, normal GC
// - HIGH: Shrink pools, aggressive GC
// - CRITICAL: Emergency mode, clear caches
```

### Manual Memory Optimization

```php
// Track objects for lifecycle management
$memoryManager->trackObject('cache', $cacheObject, [
    'ttl' => 300, // 5 minutes
]);

// Force garbage collection
$memoryManager->forceGC();

// Get memory status
$status = $memoryManager->getStatus();
echo "Memory pressure: " . $status['pressure'] . "\n";
```

## Distributed Pools

### Setup

```php
use PivotPHP\Core\Pool\Distributed\DistributedPoolManager;

$distributedPool = new DistributedPoolManager([
    'coordination' => 'redis',
    'namespace' => 'myapp:pools',
    'sync_interval' => 5,        // Sync every 5 seconds
    'leader_election' => true,
    'rebalance_interval' => 60,  // Rebalance every minute
]);

// Instances automatically:
// - Share pool objects
// - Elect a leader for coordination
// - Rebalance loads across instances
```

### Cross-Instance Sharing

```php
// Contribute excess objects
$distributedPool->contribute($excessObjects, 'request');

// Borrow from other instances
$objects = $distributedPool->borrow(10, 'request');

// Check global status (leader only)
$globalStats = $distributedPool->getGlobalStats();
```

## Performance Monitoring

### Real-Time Metrics

```php
$monitor = HighPerformanceMode::getMonitor();

// Get live metrics
$live = $monitor->getLiveMetrics();
echo "Current Load: " . $live['current_load'] . " req/s\n";
echo "Memory Pressure: " . ($live['memory_pressure'] * 100) . "%\n";
echo "P99 Latency: " . $live['p99_latency'] . "ms\n";

// Get detailed performance metrics
$metrics = $monitor->getPerformanceMetrics();
print_r($metrics['latency']); // p50, p90, p95, p99
print_r($metrics['throughput']); // rps, success_rate
```

### Custom Metrics

```php
// Record custom metrics
$monitor->recordMetric('api_calls', 1, ['endpoint' => '/users']);
$monitor->recordMetric('processing_time', 45.5, ['operation' => 'data_sync']);

// Export for monitoring systems
$export = $monitor->export(); // Prometheus-compatible format
```

### Performance Alerts

```php
// Automatic alerts based on thresholds
$monitor->setAlertThresholds([
    'latency_p99' => 1000,    // Alert if P99 > 1 second
    'error_rate' => 0.05,     // Alert if errors > 5%
    'memory_usage' => 0.8,    // Alert if memory > 80%
]);

// Check active alerts
$alerts = $monitor->getAlerts();
foreach ($alerts as $alert) {
    notifyOps($alert['message'], $alert['severity']);
}
```

## Best Practices

### 1. Choose the Right Profile

- **Standard**: Default for most applications
- **High**: APIs with >1000 req/s
- **Extreme**: APIs with >10,000 req/s

### 2. Monitor and Adjust

```php
// Monitor pool efficiency
$stats = OptimizedHttpFactory::getPoolStats();
if ($stats['efficiency']['request'] < 50) {
    // Pool size might be too small
    adjustPoolSize();
}
```

### 3. Handle Degradation Gracefully

```php
// Check system health
$health = HighPerformanceMode::getSystemHealth();
if ($health['status'] === 'degraded') {
    // Reduce functionality, not availability
    disableNonCriticalFeatures();
}
```

### 4. Configure Middleware Order

```php
// Optimal middleware order
$app->middleware('rate-limiter');    // First line of defense
$app->middleware('load-shedder');     // Prevent overload
$app->middleware('circuit-breaker');   // Isolate failures
$app->middleware('your-auth');        // Your middleware
```

### 5. Use Priority Headers

```php
// For critical endpoints
$request->headers['X-Priority'] = 'high';

// For batch operations
$request->headers['X-Priority'] = 'low';
```

## Benchmarks

### Performance Improvements

| Feature | v1.0.0 | v1.1.0 | Improvement |
|---------|--------|--------|-------------|
| Request/Response Creation | 2,000 ops/s | 50,000 ops/s | 25x |
| Memory Usage (1K requests) | 100MB | 20MB | 80% reduction |
| P99 Latency | 50ms | 5ms | 90% reduction |
| Max Throughput | 5,000 req/s | 50,000 req/s | 10x |

### Resource Usage

| Profile | Memory | CPU | Recommended For |
|---------|--------|-----|-----------------|
| Standard | +10MB | +5% | <1K req/s |
| High | +50MB | +10% | 1K-10K req/s |
| Extreme | +200MB | +20% | >10K req/s |

## Troubleshooting

### High Memory Usage

```php
// Check pool sizes
$stats = $pool->getStats();
if ($stats['pool_sizes']['request'] > 1000) {
    // Pool might be too large
    $pool->reset(); // Reset to initial size
}
```

### Circuit Breaker Always Open

```php
// Check circuit status
$status = $app->getMiddleware('circuit-breaker')->getCircuitStatus();
if ($status['error_rate'] > 50) {
    // Backend service issues
    checkBackendHealth();
}
```

### Performance Degradation

```php
// Get diagnostics
$diag = HighPerformanceMode::getDiagnostics();
print_r($diag['bottlenecks']);
print_r($diag['recommendations']);
```

## Migration from v1.0.x

1. **No Breaking Changes**: v1.1.0 is fully backward compatible
2. **Opt-in Features**: High-performance features are disabled by default
3. **Gradual Adoption**: Enable features incrementally

```php
// Start with standard profile
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_STANDARD);

// Monitor for a week
// If stable, upgrade to HIGH profile
// Only use EXTREME if really needed
```

## Conclusion

PivotPHP v1.1.0's high-performance features provide enterprise-grade performance while maintaining simplicity. Start with the standard profile and adjust based on your needs.

For more details, see:
- [Architecture Guide](./ARCHITECTURE.md)
- [Performance Tuning](./PERFORMANCE_TUNING.md)
- [Monitoring Setup](./MONITORING.md)