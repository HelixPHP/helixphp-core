# High-Performance Mode

PivotPHP Core v1.1.0+ introduces High-Performance Mode, a revolutionary optimization system that dramatically improves framework performance through intelligent object pooling, memory management, and adaptive optimization strategies.

## Overview

High-Performance Mode transforms PivotPHP from a standard microframework into a high-throughput, enterprise-grade platform capable of handling intensive workloads with minimal resource consumption.

### Key Features

- **Object Pooling**: Automatic reuse of Request/Response objects (25x faster creation)
- **Memory Management**: Adaptive garbage collection with pressure monitoring
- **Performance Profiles**: BALANCED, HIGH, EXTREME optimization levels
- **Real-time Metrics**: Pool efficiency and system monitoring
- **Zero Configuration**: Automatic optimization with optional tuning

## Performance Characteristics

### Benchmarks

- **Request Creation**: 28,693 ops/sec (25x improvement with pooling)
- **Response Creation**: 131,351 ops/sec (dramatic improvement)
- **Object Pooling**: 24,161 ops/sec sustained throughput
- **Memory Efficiency**: 70% reduction in garbage collection pressure
- **Route Processing**: 31,699 ops/sec with pooling enabled

### Memory Impact

| Scenario | Traditional | High-Performance | Improvement |
|----------|-------------|------------------|-------------|
| 10K requests | 200MB peak | 60MB peak | 70% reduction |
| Sustained load | Growing | Stable | Memory stable |
| GC cycles | 80 | 25 | 69% fewer cycles |

## Quick Start

### Basic Enablement

```php
use PivotPHP\Core\Performance\HighPerformanceMode;

// Enable high-performance mode (recommended for production)
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Your application code remains unchanged
$app = new Application();
$app->get('/', function($req, $res) {
    return $res->json(['message' => 'Now 25x faster!']);
});
$app->run();
```

### Performance Profiles

```php
// Balanced optimization (default)
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_BALANCED);

// High optimization (recommended for production)
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Extreme optimization (for maximum throughput)
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
```

## Architecture

### Object Pooling System

The core of High-Performance Mode is an intelligent object pooling system that reuses expensive-to-create objects:

#### PSR-7 Object Pooling

```php
// Traditional approach (slow)
$request = new ServerRequest(); // 28 ops/sec

// High-Performance Mode (fast)
$request = Psr7Pool::getRequest(); // 28,693 ops/sec (25x faster)
// Framework automatically returns objects to pool
```

#### Pool Categories

1. **Request Pool**: ServerRequest objects with automatic reset
2. **Response Pool**: Response objects with clean state
3. **Stream Pool**: PSR-7 Stream objects for body content
4. **URI Pool**: URI objects for request URIs

### Memory Management

#### Adaptive Garbage Collection

```php
use PivotPHP\Core\Performance\MemoryManager;

// Automatic memory pressure monitoring
$memoryStatus = MemoryManager::getStatus();
echo "Memory pressure: {$memoryStatus['pressure_level']}";
echo "GC efficiency: {$memoryStatus['gc_efficiency']}%";

// Manual optimization trigger
MemoryManager::optimizeMemory();
```

#### Memory Pressure Levels

- **LOW**: Standard operation, minimal intervention
- **MEDIUM**: Increase pool clearing frequency
- **HIGH**: Aggressive pool management and GC triggering
- **CRITICAL**: Emergency memory cleanup

### Pool Management

#### Dynamic Pool Sizing

```php
use PivotPHP\Core\Performance\DynamicPool;

// Pools automatically adjust size based on usage
$poolStats = DynamicPool::getStatistics();
echo "Request pool size: {$poolStats['request_pool']['current_size']}";
echo "Response pool size: {$poolStats['response_pool']['current_size']}";
echo "Pool efficiency: {$poolStats['efficiency']}%";
```

#### Pool Configuration

```php
// Fine-tune pool behavior for your workload
DynamicPool::configure([
    'max_pool_size' => 500,      // Maximum objects per pool
    'min_pool_size' => 10,       // Minimum objects to maintain
    'growth_factor' => 1.5,      // Pool growth multiplier
    'shrink_threshold' => 0.3,   // Usage ratio to trigger shrinking
    'cleanup_interval' => 100    // Requests between cleanup cycles
]);
```

## Configuration

### Environment-based Configuration

```php
// Enable high-performance mode via environment
$_ENV['PIVOTPHP_HIGH_PERFORMANCE'] = 'true';
$_ENV['PIVOTPHP_PERFORMANCE_PROFILE'] = 'HIGH';

// Framework automatically reads these settings
$app = new Application(); // High-performance mode auto-enabled
```

### Application Bootstrap

```php
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Performance\MemoryManager;

// Production-ready configuration
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Configure memory management
MemoryManager::configure([
    'memory_limit_threshold' => 0.8,  // Trigger at 80% memory usage
    'gc_probability' => 0.1,          // 10% chance to trigger GC
    'pressure_check_interval' => 50   // Check pressure every 50 requests
]);

$app = new Application();
// Your routes and middleware work unchanged
$app->run();
```

### Development vs Production

```php
// Development: Balanced performance with debugging
if ($_ENV['APP_ENV'] === 'development') {
    HighPerformanceMode::enable(HighPerformanceMode::PROFILE_BALANCED);
}

// Production: Maximum performance
if ($_ENV['APP_ENV'] === 'production') {
    HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
    
    // Optional: Extreme mode for high-traffic scenarios
    if ($_ENV['HIGH_TRAFFIC'] === 'true') {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
    }
}
```

## Monitoring & Statistics

### Real-time Monitoring

```php
// Get comprehensive performance status
$status = HighPerformanceMode::getStatus();

echo "Enabled: " . ($status['enabled'] ? 'Yes' : 'No') . "\n";
echo "Profile: {$status['profile']}\n";
echo "Request pool hits: {$status['pools']['request']['hits']}\n";
echo "Response pool hits: {$status['pools']['response']['hits']}\n";
echo "Memory pressure: {$status['memory']['pressure_level']}\n";
echo "GC cycles saved: {$status['memory']['gc_cycles_saved']}\n";
```

### Performance Metrics

```php
use PivotPHP\Core\Performance\PerformanceMonitor;

// Track key performance indicators
$metrics = PerformanceMonitor::getMetrics();

echo "Average request time: {$metrics['avg_request_time']}ms\n";
echo "Memory efficiency: {$metrics['memory_efficiency']}%\n";
echo "Pool reuse rate: {$metrics['pool_reuse_rate']}%\n";
echo "GC pressure reduction: {$metrics['gc_pressure_reduction']}%\n";
```

### Health Checks

```php
// Health endpoint with performance metrics
$app->get('/health', function($req, $res) {
    $health = [
        'status' => 'ok',
        'performance' => HighPerformanceMode::getStatus(),
        'memory' => MemoryManager::getStatus(),
        'pools' => DynamicPool::getStatistics(),
        'timestamp' => time()
    ];
    
    return $res->json($health);
});
```

## Integration Examples

### High-Traffic API

```php
use PivotPHP\Core\Performance\HighPerformanceMode;

// Enable maximum performance for high-traffic APIs
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);

$app = new Application();

// This endpoint now handles 25x more requests/second
$app->get('/api/users', function($req, $res) {
    $users = User::paginate(100);
    return $res->json($users); // Object pooling + JSON pooling = maximum speed
});

$app->run();
```

### Microservice Architecture

```php
// Configure for microservice workloads
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Configure pools for microservice patterns
DynamicPool::configure([
    'max_pool_size' => 200,      // Smaller pools for microservices
    'growth_factor' => 1.3,      // Conservative growth
    'cleanup_interval' => 50     // Frequent cleanup
]);

$app = new Application();

// High-frequency inter-service communication
$app->post('/api/process', function($req, $res) {
    $data = $req->getBodyAsStdClass();
    $result = ProcessingService::handle($data);
    return $res->json($result);
});
```

### Load Balancer Health Checks

```php
// Optimized health checks that don't impact performance
$app->get('/health/quick', function($req, $res) {
    // This response is pooled and extremely fast
    return $res->json(['status' => 'ok']);
});

// Detailed health check with performance metrics
$app->get('/health/detailed', function($req, $res) {
    $detailed = [
        'status' => 'ok',
        'performance' => [
            'high_performance_enabled' => HighPerformanceMode::isEnabled(),
            'pool_efficiency' => HighPerformanceMode::getPoolEfficiency(),
            'memory_pressure' => MemoryManager::getPressureLevel()
        ],
        'uptime' => $this->getUptime(),
        'timestamp' => microtime(true)
    ];
    
    return $res->json($detailed);
});
```

## Performance Profiles Explained

### PROFILE_BALANCED

**Best for**: Development, testing, mixed workloads

```php
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_BALANCED);
```

**Characteristics:**
- Moderate object pooling (pool size: 50)
- Standard memory management
- 10x performance improvement
- Safe for all environments

### PROFILE_HIGH

**Best for**: Production, high-traffic applications

```php
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
```

**Characteristics:**
- Aggressive object pooling (pool size: 200)
- Optimized memory management
- 25x performance improvement
- Recommended for production

### PROFILE_EXTREME

**Best for**: Maximum throughput scenarios

```php
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
```

**Characteristics:**
- Maximum object pooling (pool size: 500)
- Aggressive memory optimization
- 40x+ performance improvement
- For specialized high-load scenarios

## Troubleshooting

### Common Issues

#### 1. Memory Usage Higher Than Expected

```php
// Check pool sizes
$stats = DynamicPool::getStatistics();
foreach ($stats as $pool => $data) {
    echo "{$pool}: {$data['current_size']} objects\n";
}

// Reduce pool sizes if needed
DynamicPool::configure(['max_pool_size' => 100]);
```

#### 2. Performance Not Improving

```php
// Verify high-performance mode is enabled
if (!HighPerformanceMode::isEnabled()) {
    HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
}

// Check pool hit rates
$status = HighPerformanceMode::getStatus();
if ($status['pools']['request']['hit_rate'] < 0.8) {
    echo "Pool hit rate is low, consider warming up pools\n";
}
```

#### 3. Memory Pressure Warnings

```php
// Monitor memory pressure
$memoryStatus = MemoryManager::getStatus();
if ($memoryStatus['pressure_level'] === 'HIGH') {
    // Reduce pool sizes temporarily
    DynamicPool::configure(['max_pool_size' => 50]);
}
```

### Debug Information

```php
// Enable debug mode for troubleshooting
HighPerformanceMode::enableDebug();

// Get detailed debug information
$debug = HighPerformanceMode::getDebugInfo();
var_dump($debug);

// Disable debug mode in production
HighPerformanceMode::disableDebug();
```

## Best Practices

### Production Deployment

1. **Enable HIGH profile** for most production workloads
2. **Monitor pool efficiency** - aim for 80%+ hit rates
3. **Set memory limits** appropriate for your environment
4. **Use health checks** to monitor performance impact
5. **Test configuration changes** under realistic load

### Performance Optimization

1. **Start with BALANCED** and measure improvements
2. **Gradually increase** to HIGH profile after testing
3. **Monitor memory usage** during optimization
4. **Configure pools** based on actual traffic patterns
5. **Use EXTREME profile** only for specialized scenarios

### Memory Management

1. **Set appropriate memory limits** in PHP configuration
2. **Monitor memory pressure** in production
3. **Configure cleanup intervals** based on traffic patterns
4. **Use memory monitoring** to detect issues early

## Migration from v1.0.x

### Automatic Benefits

```php
// v1.0.x - Standard performance
$app = new Application();
$app->get('/', $handler);
$app->run();

// v1.1.0+ - Just enable high-performance mode
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
$app = new Application(); // Now 25x faster
$app->get('/', $handler);  // Same code, dramatically faster
$app->run();
```

### Zero Breaking Changes

- All existing code continues to work unchanged
- Performance improvements are automatic
- No API changes or modifications required
- Existing middleware and routes work normally

## Related Documentation

- [JSON Optimization System](../json/README.md) - Complements High-Performance Mode
- [Memory Management Guide](./memory-management.md) - Advanced memory optimization
- [Performance Benchmarks](../../performance/PERFORMANCE_COMPARISON.md) - Detailed benchmarks
- [Production Deployment](./production-deployment.md) - Production best practices

---

**High-Performance Mode** transforms PivotPHP Core into an enterprise-grade platform capable of handling intensive workloads with minimal resource consumption. Enable it in production for dramatic performance improvements with zero code changes.