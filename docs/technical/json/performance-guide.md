# JSON Performance Optimization Guide

This guide provides detailed information about optimizing JSON performance in PivotPHP Core v1.1.1 and later.

## Understanding JSON Pooling

JSON pooling is a performance optimization technique that reuses buffer objects to reduce memory allocation overhead and garbage collection pressure.

### When Pooling Helps

JSON pooling provides the most benefit in these scenarios:

1. **High-Frequency Operations**: APIs processing hundreds or thousands of JSON requests per second
2. **Medium to Large Datasets**: Arrays with 10+ elements, objects with 5+ properties
3. **Sustained Load**: Long-running applications with continuous JSON processing
4. **Memory-Constrained Environments**: Applications where garbage collection pressure matters

### When Pooling Doesn't Help

For these scenarios, traditional `json_encode()` may be faster:

1. **Small Data**: Simple objects with 1-3 properties
2. **Infrequent Operations**: Applications processing <10 JSON operations per second
3. **Large Single Objects**: Very large objects (>1MB) that don't benefit from buffer reuse

## Performance Characteristics

### Benchmark Results

Based on comprehensive testing with PivotPHP Core v1.1.1:

| Scenario | Traditional | Pooled | Improvement |
|----------|-------------|--------|-------------|
| Small JSON (< 1KB) | 2.5M ops/sec | 2.5M ops/sec | 0% (fallback) |
| Medium JSON (1-10KB) | 400K ops/sec | 600K ops/sec | +50% |
| Large JSON (10-100KB) | 180K ops/sec | 300K ops/sec | +67% |
| Sustained Load (60s) | 85K ops/sec | 101K ops/sec | +19% |

### Memory Usage

| Scenario | Traditional Memory | Pooled Memory | Reduction |
|----------|-------------------|---------------|-----------|
| 10K operations | 150MB peak | 45MB peak | 70% |
| Sustained load | 200MB growing | 60MB stable | 70% |
| GC cycles | 50 collections | 15 collections | 70% |

## Configuration for Different Workloads

### API Server (High Throughput)

```php
// Optimized for 1000+ requests/second
JsonBufferPool::configure([
    'max_pool_size' => 500,
    'default_capacity' => 8192,
    'size_categories' => [
        'small' => 2048,    // User profiles, small responses
        'medium' => 8192,   // Product lists, search results  
        'large' => 32768,   // Detailed reports, bulk data
        'xlarge' => 131072  // Export operations, large datasets
    ]
]);
```

### Microservice (Moderate Load)

```php
// Balanced configuration for microservices
JsonBufferPool::configure([
    'max_pool_size' => 100,
    'default_capacity' => 4096,
    'size_categories' => [
        'small' => 1024,
        'medium' => 4096,
        'large' => 16384,
        'xlarge' => 65536
    ]
]);
```

### Background Workers

```php
// Memory-efficient configuration for workers
JsonBufferPool::configure([
    'max_pool_size' => 50,
    'default_capacity' => 4096,
    'size_categories' => [
        'small' => 1024,
        'medium' => 4096,
        'large' => 16384,
        'xlarge' => 65536
    ]
]);
```

### Development/Testing

```php
// Minimal configuration for development
JsonBufferPool::configure([
    'max_pool_size' => 20,
    'default_capacity' => 2048,
    'size_categories' => [
        'small' => 512,
        'medium' => 2048,
        'large' => 8192,
        'xlarge' => 32768
    ]
]);
```

## Monitoring and Optimization

### Key Metrics

Monitor these metrics to optimize pool performance:

```php
$stats = JsonBufferPool::getStatistics();

// Efficiency metrics
$reuseRate = $stats['reuse_rate'];        // Target: >80%
$totalOps = $stats['total_operations'];   // Volume indicator
$currentUsage = $stats['current_usage'];  // Memory usage
$peakUsage = $stats['peak_usage'];        // Capacity planning

// Pool utilization
$poolSizes = $stats['pool_sizes'];        // Buffer distribution
```

### Optimization Indicators

| Metric | Good | Warning | Action Needed |
|--------|------|---------|---------------|
| Reuse Rate | >80% | 50-80% | <50% |
| Current Usage | <100 | 100-500 | >500 |
| Pool Growth | Stable | Slow growth | Rapid growth |

### Tuning Guidelines

#### Low Reuse Rate (<50%)

**Possible Causes:**
- Pool sizes too small for workload
- Data sizes don't match pool categories
- Mixed workload with varying data sizes

**Solutions:**
```php
// Increase pool sizes
JsonBufferPool::configure(['max_pool_size' => 200]);

// Add intermediate size categories
JsonBufferPool::configure([
    'size_categories' => [
        'tiny' => 512,
        'small' => 2048,
        'medium' => 8192,
        'large' => 32768,
        'xlarge' => 131072
    ]
]);
```

#### High Memory Usage

**Possible Causes:**
- Pool sizes too large
- Memory leaks in application code
- Buffers not being returned properly

**Solutions:**
```php
// Reduce pool sizes
JsonBufferPool::configure(['max_pool_size' => 50]);

// Monitor for leaks
$stats = JsonBufferPool::getStatistics();
if ($stats['current_usage'] > $stats['detailed_stats']['deallocations']) {
    // Investigate buffer leaks
}
```

#### Performance Regression

**Possible Causes:**
- Pooling overhead for small data
- Incorrect pool configuration
- System memory pressure

**Solutions:**
```php
// Check if automatic detection is working
$response->json($smallData); // Should use json_encode()
$response->json($largeData); // Should use pooling

// Verify configuration
JsonBufferPool::configure(['default_capacity' => 4096]);
```

## Advanced Usage Patterns

### Streaming Large Datasets

```php
function streamLargeDataset($data) {
    $buffer = JsonBufferPool::getBuffer(65536); // Start with 64KB
    
    try {
        $buffer->append('{"items":[');
        
        $first = true;
        foreach ($data as $item) {
            if (!$first) {
                $buffer->append(',');
            }
            
            $buffer->appendJson($item);
            $first = false;
            
            // Flush if buffer is getting large
            if ($buffer->getSize() > 50000) {
                echo $buffer->finalize();
                $buffer->reset();
                $buffer->append(''); // Continue stream
            }
        }
        
        $buffer->append(']}');
        echo $buffer->finalize();
        
    } finally {
        JsonBufferPool::returnBuffer($buffer);
    }
}
```

### Batch Processing

```php
function processBatch($items) {
    $optimalSize = JsonBufferPool::getOptimalCapacity($items);
    $buffer = JsonBufferPool::getBuffer($optimalSize);
    
    try {
        $results = [];
        
        foreach ($items as $item) {
            $buffer->appendJson($item);
            $json = $buffer->finalize();
            
            $results[] = processJsonItem($json);
            $buffer->reset();
        }
        
        return $results;
        
    } finally {
        JsonBufferPool::returnBuffer($buffer);
    }
}
```

### Custom Pool Management

```php
class CustomJsonProcessor {
    private $buffer;
    
    public function __construct() {
        $this->buffer = JsonBufferPool::getBuffer(16384);
    }
    
    public function processItem($data) {
        $this->buffer->appendJson($data);
        $json = $this->buffer->finalize();
        $this->buffer->reset();
        
        return $this->processJson($json);
    }
    
    public function __destruct() {
        if ($this->buffer) {
            JsonBufferPool::returnBuffer($this->buffer);
        }
    }
}
```

## Production Monitoring

### Health Checks

```php
function jsonPoolHealthCheck() {
    $stats = JsonBufferPool::getStatistics();
    
    $health = [
        'status' => 'healthy',
        'issues' => []
    ];
    
    // Check reuse rate
    if ($stats['reuse_rate'] < 50 && $stats['total_operations'] > 1000) {
        $health['status'] = 'warning';
        $health['issues'][] = "Low reuse rate: {$stats['reuse_rate']}%";
    }
    
    // Check memory usage
    if ($stats['current_usage'] > 1000) {
        $health['status'] = 'warning';  
        $health['issues'][] = "High memory usage: {$stats['current_usage']} buffers";
    }
    
    // Check pool growth
    $growth = $stats['peak_usage'] - $stats['current_usage'];
    if ($growth > 500) {
        $health['status'] = 'warning';
        $health['issues'][] = "Pool growth detected: {$growth} buffers";
    }
    
    return $health;
}
```

### Metrics Collection

```php
// Collect metrics for APM/monitoring systems
function collectJsonPoolMetrics() {
    $stats = JsonBufferPool::getStatistics();
    
    return [
        'json_pool_reuse_rate' => $stats['reuse_rate'],
        'json_pool_operations_total' => $stats['total_operations'],
        'json_pool_buffers_current' => $stats['current_usage'],
        'json_pool_buffers_peak' => $stats['peak_usage'],
        'json_pool_allocations' => $stats['detailed_stats']['allocations'],
        'json_pool_deallocations' => $stats['detailed_stats']['deallocations'],
        'json_pool_reuses' => $stats['detailed_stats']['reuses']
    ];
}
```

### Alerting

```php
// Set up alerts for pool issues
function checkJsonPoolAlerts() {
    $stats = JsonBufferPool::getStatistics();
    
    // Memory leak detection
    if ($stats['current_usage'] > 2000) {
        alert('CRITICAL: JSON pool memory leak detected');
    }
    
    // Performance degradation
    if ($stats['reuse_rate'] < 30 && $stats['total_operations'] > 10000) {
        alert('WARNING: JSON pool efficiency degraded');
    }
    
    // Capacity planning
    if ($stats['peak_usage'] > 800) {
        alert('INFO: Consider increasing JSON pool capacity');
    }
}
```

## Troubleshooting Common Issues

### Issue: Low Performance After Upgrade

**Symptoms:** JSON operations slower after upgrading to v1.1.1

**Diagnosis:**
```php
// Check if pooling is being used appropriately
$stats = JsonBufferPool::getStatistics();
if ($stats['total_operations'] === 0) {
    echo "Pooling not being used - check data sizes\n";
}
```

**Solution:**
- Verify data meets pooling criteria (arrays 10+ elements)
- Check manual usage is correct
- Consider forcing pooling for specific cases

### Issue: Memory Growth

**Symptoms:** Application memory usage grows over time

**Diagnosis:**
```php
$stats = JsonBufferPool::getStatistics();
$leaked = $stats['detailed_stats']['allocations'] - $stats['detailed_stats']['deallocations'];
if ($leaked > 100) {
    echo "Potential buffer leak: {$leaked} buffers not returned\n";
}
```

**Solution:**
- Review manual buffer usage for proper `returnBuffer()` calls
- Use try/finally blocks to ensure buffers are returned
- Reduce max_pool_size if needed

### Issue: Inconsistent Performance

**Symptoms:** Variable JSON processing times

**Diagnosis:**
```php
// Monitor pool sizes over time
$stats = JsonBufferPool::getStatistics();
foreach ($stats['pool_sizes'] as $pool => $size) {
    echo "{$pool}: {$size} buffers\n";
}
```

**Solution:**
- Adjust size categories to match actual data patterns
- Pre-warm pools during application startup
- Consider workload-specific configurations

## Best Practices Summary

1. **Let the system work automatically** - The default configuration works well for most applications
2. **Monitor reuse rates** - Target 80%+ for high-traffic applications  
3. **Size pools appropriately** - Match pool configuration to actual workload
4. **Use manual pooling sparingly** - Only when automatic detection isn't sufficient
5. **Implement health checks** - Monitor pool metrics in production
6. **Test configuration changes** - Benchmark before deploying pool changes
7. **Handle errors gracefully** - Always use try/finally for manual buffer management

The JSON optimization system in PivotPHP Core v1.1.1 provides significant performance improvements with minimal configuration required. Focus on monitoring and gradual optimization rather than complex initial setup.