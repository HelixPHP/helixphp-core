# PivotPHP v1.1.0 Performance Tuning Guide

## Overview

This guide provides detailed instructions for tuning PivotPHP v1.1.0 for maximum performance in production environments.

## Quick Tuning Checklist

- [ ] Choose appropriate performance profile
- [ ] Configure pool sizes based on load
- [ ] Set proper memory limits
- [ ] Enable only needed middleware
- [ ] Configure monitoring sample rates
- [ ] Optimize PHP and server settings
- [ ] Set up proper caching
- [ ] Configure distributed pools (if multi-instance)

## Performance Profiles

### Choosing the Right Profile

| Profile | Use Case | Memory Overhead | CPU Overhead |
|---------|----------|-----------------|--------------|
| STANDARD | <1,000 req/s | +10MB | +5% |
| HIGH | 1,000-10,000 req/s | +50MB | +10% |
| EXTREME | >10,000 req/s | +200MB | +20% |

```php
// Start conservatively
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_STANDARD);

// Monitor for a week, then upgrade if needed
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
```

## Pool Tuning

### Calculating Optimal Pool Sizes

```php
// Formula: pool_size = concurrent_requests * 1.2
// Example: 100 concurrent requests = 120 pool size

$poolConfig = [
    'initial_size' => 120,
    'max_size' => 600,      // 5x initial
    'emergency_limit' => 1200, // 10x initial
];
```

### Pool Configuration Examples

**Low Traffic API** (<100 req/s):
```php
$pool = new DynamicPool([
    'initial_size' => 20,
    'max_size' => 100,
    'emergency_limit' => 200,
    'scale_threshold' => 0.8,
    'shrink_threshold' => 0.2,
]);
```

**Medium Traffic API** (100-1,000 req/s):
```php
$pool = new DynamicPool([
    'initial_size' => 100,
    'max_size' => 500,
    'emergency_limit' => 1000,
    'scale_threshold' => 0.7,
    'shrink_threshold' => 0.3,
]);
```

**High Traffic API** (>1,000 req/s):
```php
$pool = new DynamicPool([
    'initial_size' => 500,
    'max_size' => 2000,
    'emergency_limit' => 5000,
    'scale_threshold' => 0.6,
    'shrink_threshold' => 0.4,
    'scale_factor' => 2.0,     // Aggressive scaling
    'cooldown_period' => 30,   // Faster reactions
]);
```

## Memory Tuning

### PHP Memory Settings

```ini
; php.ini settings for high-performance
memory_limit = 512M          ; Minimum for HIGH profile
max_execution_time = 30      ; Prevent long-running requests
opcache.enable = 1           ; Essential for performance
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0  ; Disable in production
```

### Memory Manager Configuration

```php
$memoryConfig = [
    'gc_strategy' => MemoryManager::STRATEGY_ADAPTIVE,
    'gc_threshold' => 0.7,      // GC at 70% memory
    'emergency_gc' => 0.9,      // Emergency at 90%
    'check_interval' => 5,      // Check every 5 seconds
    'pool_adjustments' => [
        'low' => 1.2,          // Grow pools by 20%
        'medium' => 1.0,       // Maintain size
        'high' => 0.7,         // Shrink by 30%
        'critical' => 0.5,     // Shrink by 50%
    ],
];
```

## Middleware Tuning

### Load Shedder

```php
// Conservative (prefer availability)
$app->middleware('load-shedder', [
    'threshold' => 0.9,         // Only shed at 90% load
    'strategy' => 'priority',
    'min_success_rate' => 0.8,  // Keep 80% success
]);

// Aggressive (prefer performance)
$app->middleware('load-shedder', [
    'threshold' => 0.7,         // Start shedding at 70%
    'strategy' => 'adaptive',
    'min_success_rate' => 0.5,  // Allow 50% rejection
]);
```

### Circuit Breaker

```php
// Sensitive (quick to open)
$app->middleware('circuit-breaker', [
    'failure_threshold' => 10,   // 10 failures/minute
    'success_threshold' => 5,    // 5 successes to close
    'timeout' => 60,            // 1 minute timeout
]);

// Tolerant (slow to open)
$app->middleware('circuit-breaker', [
    'failure_threshold' => 100,  // 100 failures/minute
    'success_threshold' => 20,   // 20 successes to close
    'timeout' => 30,            // 30 second timeout
]);
```

### Rate Limiter

```php
// Per-user limiting
$app->middleware('rate-limiter', [
    'max_requests' => 100,
    'window' => 60,
    'key_by' => 'user',
    'burst_size' => 20,         // Allow short bursts
]);

// Global API limiting
$app->middleware('rate-limiter', [
    'max_requests' => 10000,
    'window' => 60,
    'key_by' => 'global',
    'burst_size' => 1000,
]);
```

## Monitoring Tuning

### Sample Rates

```php
// Development: 100% sampling
$monitor = new PerformanceMonitor([
    'sample_rate' => 1.0,
]);

// Production: Balanced sampling
$monitor = new PerformanceMonitor([
    'sample_rate' => 0.1,       // 10% of requests
    'always_sample_errors' => true,
]);

// High-volume: Minimal sampling
$monitor = new PerformanceMonitor([
    'sample_rate' => 0.01,      // 1% of requests
    'percentiles' => [50, 99],  // Only P50 and P99
]);
```

### Metric Retention

```php
$monitor = new PerformanceMonitor([
    'metric_window' => 300,      // 5 minutes of detailed data
    'aggregation_interval' => 60, // Aggregate every minute
    'export_interval' => 10,     // Export every 10 seconds
]);
```

## Server Tuning

### Nginx Configuration

```nginx
# nginx.conf
worker_processes auto;
worker_rlimit_nofile 65535;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    keepalive_timeout 65;
    keepalive_requests 100;
    
    # Enable caching
    open_file_cache max=1000 inactive=20s;
    open_file_cache_valid 30s;
    open_file_cache_min_uses 2;
    
    # Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 10240;
    gzip_types text/plain text/css text/xml application/json;
}
```

### PHP-FPM Configuration

```ini
; php-fpm.conf
pm = dynamic
pm.max_children = 100
pm.start_servers = 20
pm.min_spare_servers = 10
pm.max_spare_servers = 30
pm.max_requests = 1000

; Enable status page
pm.status_path = /status
```

## Database Connection Pooling

```php
// Configure connection pool for database
$dbConfig = [
    'pool_size' => 20,
    'max_idle_time' => 60,
    'connection_timeout' => 5,
    'retry_attempts' => 3,
];
```

## Distributed Pool Tuning

### Redis Configuration

```redis
# redis.conf
maxmemory 2gb
maxmemory-policy allkeys-lru
tcp-keepalive 60
timeout 300

# Persistence (disable for pure cache)
save ""
appendonly no
```

### Distributed Pool Settings

```php
$distributed = new DistributedPoolManager([
    'coordination' => 'redis',
    'sync_interval' => 5,        // 5 second sync
    'leader_ttl' => 30,         // 30 second leadership
    'rebalance_interval' => 60,  // Rebalance every minute
    'borrow_timeout' => 5,       // 5 second timeout
    'min_pool_size' => 50,      // Per instance minimum
    'max_pool_size' => 1000,    // Per instance maximum
]);
```

## Performance Testing

### Load Testing Configuration

```bash
# Use Apache Bench
ab -n 10000 -c 100 -k http://api.example.com/

# Use wrk for more realistic load
wrk -t12 -c400 -d30s --latency http://api.example.com/
```

### Monitoring During Tests

```php
// Enable detailed monitoring during load tests
HighPerformanceMode::enable([
    'monitoring' => [
        'enabled' => true,
        'sample_rate' => 1.0,    // 100% during tests
        'detailed_metrics' => true,
    ],
]);
```

## Troubleshooting Performance Issues

### High Memory Usage

```php
// Check pool sizes
$stats = $pool->getStats();
foreach ($stats['pool_sizes'] as $type => $size) {
    if ($size > 1000) {
        error_log("Pool $type is too large: $size");
        // Consider reducing max_size
    }
}
```

### High Latency

```php
// Check circuit breaker states
$circuits = $app->getMiddleware('circuit-breaker')->getCircuitStatus();
foreach ($circuits as $name => $status) {
    if ($status['state'] !== 'closed') {
        error_log("Circuit $name is {$status['state']}");
        // Backend service issues
    }
}
```

### Low Throughput

```php
// Check load shedding
$shedderStats = $app->getMiddleware('load-shedder')->getStats();
if ($shedderStats['rejection_rate'] > 0.1) {
    error_log("Shedding {$shedderStats['rejection_rate']}% of requests");
    // Increase capacity or adjust threshold
}
```

## Best Practices

### 1. Start Conservative

```php
// Begin with standard settings
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_STANDARD);

// Monitor for stability
// Gradually increase based on metrics
```

### 2. Monitor Key Metrics

```php
// Set up alerts for:
// - P99 latency > 100ms
// - Error rate > 1%
// - Memory usage > 80%
// - Pool efficiency < 50%
```

### 3. Regular Maintenance

```php
// Weekly tasks:
// - Review performance metrics
// - Adjust pool sizes if needed
// - Check error logs
// - Update configuration

// Monthly tasks:
// - Load test current configuration
// - Plan capacity for growth
// - Review and optimize slow endpoints
```

### 4. Capacity Planning

```php
// Calculate required resources:
// peak_requests_per_second * 1.5 (headroom)
// = required_capacity

// Example: 1000 req/s peak
// 1000 * 1.5 = 1500 req/s capacity needed
// Configure pools and limits accordingly
```

## Production Checklist

- [ ] PHP OPcache enabled and tuned
- [ ] Memory limits appropriate for load
- [ ] Connection pooling configured
- [ ] Monitoring and alerting active
- [ ] Load shedding thresholds set
- [ ] Circuit breakers configured
- [ ] Pool sizes optimized
- [ ] Distributed pools set up (if needed)
- [ ] Performance baseline established
- [ ] Runbook for issues prepared