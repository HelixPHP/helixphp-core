# JSON Optimization System

PivotPHP Core v1.1.1 introduces a revolutionary JSON optimization system that dramatically improves performance for JSON operations through intelligent buffer pooling and automatic optimization.

## Overview

The JSON optimization system consists of two main components:
- **JsonBuffer**: High-performance buffer for JSON operations
- **JsonBufferPool**: Intelligent pooling system for buffer reuse

These work together to provide automatic performance improvements with zero configuration required.

## Automatic Integration

The system is seamlessly integrated into the framework's core `Response::json()` method:

```php
// This code automatically benefits from pooling when appropriate
$response->json($data);
```

### Smart Detection

The system automatically activates pooling based on data characteristics:
- **Arrays**: 10 or more elements
- **Objects**: 5 or more properties  
- **Strings**: Greater than 1KB in size

For smaller datasets, the system uses traditional `json_encode()` for optimal performance.

## Manual Usage

For advanced use cases, you can interact with the pooling system directly:

```php
use PivotPHP\Core\Json\Pool\JsonBufferPool;

// Direct encoding with pooling
$json = JsonBufferPool::encodeWithPool($data);

// Get a buffer for manual operations
$buffer = JsonBufferPool::getBuffer(4096);
$buffer->appendJson(['key' => 'value']);
$buffer->append(',');
$buffer->appendJson(['another' => 'value']);
$result = $buffer->finalize();
JsonBufferPool::returnBuffer($buffer);
```

## Configuration

The pool can be configured for production workloads:

```php
JsonBufferPool::configure([
    'max_pool_size' => 200,        // Maximum buffers per pool
    'default_capacity' => 8192,    // Default buffer size (8KB)
    'size_categories' => [
        'small' => 2048,           // 2KB
        'medium' => 8192,          // 8KB  
        'large' => 32768,          // 32KB
        'xlarge' => 131072         // 128KB
    ]
]);
```

## Performance Monitoring

The system provides comprehensive statistics for monitoring and optimization:

```php
$stats = JsonBufferPool::getStatistics();

echo "Reuse Rate: {$stats['reuse_rate']}%\n";
echo "Total Operations: {$stats['total_operations']}\n";
echo "Current Usage: {$stats['current_usage']} buffers\n";
echo "Peak Usage: {$stats['peak_usage']} buffers\n";

// Pool sizes by category
foreach ($stats['pool_sizes'] as $category => $count) {
    echo "{$category}: {$count} buffers\n";
}
```

## Performance Characteristics

### Benchmarks

- **Sustained Throughput**: 101,000+ operations per second
- **Reuse Rate**: 100% in high-frequency scenarios
- **Memory Efficiency**: Significant reduction in GC pressure
- **Latency**: Consistent performance under load

### Use Cases

The system excels in:
- **High-throughput APIs** (1000+ requests/second)
- **Microservices** with frequent JSON responses
- **Real-time applications** with continuous data streaming
- **Batch processing** with large datasets

## Architecture

### Buffer Management

Buffers are organized into size-based pools:
- **buffer_1024**: 1KB buffers for small data
- **buffer_4096**: 4KB buffers for medium data
- **buffer_16384**: 16KB buffers for large data
- **buffer_65536**: 64KB buffers for extra-large data

### Pool Lifecycle

1. **Acquisition**: Get buffer from appropriate pool or create new
2. **Usage**: Append JSON data with automatic expansion
3. **Finalization**: Convert buffer contents to final JSON string
4. **Return**: Reset and return buffer to pool for reuse

### Memory Management

- **Automatic Expansion**: Buffers grow as needed
- **Efficient Reset**: Buffers are reset without reallocation
- **Pool Limits**: Configurable maximum pool sizes prevent memory bloat
- **Garbage Collection**: Unused buffers are automatically cleaned up

## Integration Examples

### API Response

```php
$app->get('/api/users', function($req, $res) {
    $users = User::all(); // Array of 100+ users
    
    // Automatically uses pooling for large dataset
    return $res->json($users);
});
```

### Streaming Data

```php
$app->get('/api/metrics/live', function($req, $res) {
    $buffer = JsonBufferPool::getBuffer(32768); // 32KB buffer
    
    try {
        $buffer->append('{"metrics":[');
        
        $first = true;
        foreach ($this->streamMetrics() as $metric) {
            if (!$first) $buffer->append(',');
            $buffer->appendJson($metric);
            $first = false;
        }
        
        $buffer->append(']}');
        $json = $buffer->finalize();
        
        return $res->setHeader('Content-Type', 'application/json')
                   ->setBody($json);
    } finally {
        JsonBufferPool::returnBuffer($buffer);
    }
});
```

### Health Check Integration

```php
$app->get('/health', function($req, $res) {
    $health = [
        'status' => 'ok',
        'json_pool' => JsonBufferPool::getStatistics(),
        'timestamp' => time()
    ];
    
    return $res->json($health);
});
```

## Best Practices

### Production Configuration

```php
// High-traffic configuration
JsonBufferPool::configure([
    'max_pool_size' => 500,
    'default_capacity' => 16384,
    'size_categories' => [
        'small' => 4096,
        'medium' => 16384,
        'large' => 65536,
        'xlarge' => 262144
    ]
]);
```

### Monitoring

Set up regular monitoring of pool statistics:

```php
// Add to your monitoring system
function checkJsonPoolHealth() {
    $stats = JsonBufferPool::getStatistics();
    
    // Alert if reuse rate is too low
    if ($stats['reuse_rate'] < 50 && $stats['total_operations'] > 1000) {
        log_warning("Low JSON pool reuse rate: {$stats['reuse_rate']}%");
    }
    
    // Alert if pool usage is growing without bounds
    if ($stats['current_usage'] > 1000) {
        log_warning("High JSON pool usage: {$stats['current_usage']} buffers");
    }
    
    return $stats;
}
```

### Error Handling

The system includes robust error handling with automatic fallback:

```php
try {
    $json = JsonBufferPool::encodeWithPool($data);
} catch (\Throwable $e) {
    // Automatic fallback to traditional encoding
    log_error("JSON pooling failed: " . $e->getMessage());
    $json = json_encode($data);
}
```

## Troubleshooting

### Common Issues

1. **Low Reuse Rate**: Check if data sizes match pool categories
2. **High Memory Usage**: Reduce max_pool_size or adjust size categories
3. **Performance Regression**: Verify pooling is being used for appropriate data sizes

### Debug Information

```php
// Enable detailed debugging
$debug = JsonBufferPool::getStatistics();
var_dump($debug['detailed_stats']);

// Clear pools for testing
JsonBufferPool::clearPools();
```

## Migration Guide

No migration is required! The system works automatically with existing code:

```php
// Before v1.1.1
$response->json($data); // Uses json_encode()

// After v1.1.1 
$response->json($data); // Automatically uses pooling when beneficial
```

For applications wanting to maximize performance, consider:
- Configuring pool sizes for your specific workload
- Adding monitoring to track pool efficiency
- Using manual pooling for specialized use cases

## Related Documentation

- [Performance Tuning Guide](../performance/)
- [HTTP Response Documentation](../http/response.md)
- [Benchmarking Results](../../performance/JSON_PERFORMANCE.md)
- [v1.1.1 Release Notes](../../releases/v1.1.1/)