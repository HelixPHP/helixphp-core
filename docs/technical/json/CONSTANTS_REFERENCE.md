# JsonBufferPool Constants Reference

This document provides comprehensive information about the public constants available in the JsonBufferPool system, introduced and enhanced in PivotPHP Core v1.1.1+.

## Overview

The JsonBufferPool exposes various constants that allow for advanced configuration, testing, and debugging. These constants provide insight into the internal workings of the JSON optimization system and enable precise control over its behavior.

## Size Estimation Constants

These constants define the estimated JSON sizes for different data types and structures:

### Array Size Estimates

```php
JsonBufferPool::EMPTY_ARRAY_SIZE;           // 2 - Size of empty array []
JsonBufferPool::SMALL_ARRAY_SIZE;           // 512 - Arrays with < 10 elements
JsonBufferPool::MEDIUM_ARRAY_SIZE;          // 2048 - Arrays with < 100 elements  
JsonBufferPool::LARGE_ARRAY_SIZE;           // 8192 - Arrays with < 1000 elements
JsonBufferPool::XLARGE_ARRAY_SIZE;          // 32768 - Arrays with >= 1000 elements
```

**Usage Example:**
```php
$arraySize = count($data);
if ($arraySize >= JsonBufferPool::LARGE_ARRAY_THRESHOLD) {
    $expectedSize = JsonBufferPool::XLARGE_ARRAY_SIZE;
} elseif ($arraySize >= JsonBufferPool::MEDIUM_ARRAY_THRESHOLD) {
    $expectedSize = JsonBufferPool::LARGE_ARRAY_SIZE;
} // ... and so on
```

### Object Size Estimates

```php
JsonBufferPool::OBJECT_BASE_SIZE;           // 100 - Base size for any object
JsonBufferPool::OBJECT_PROPERTY_OVERHEAD;   // 50 - Additional bytes per property
```

**Usage Example:**
```php
$object = new stdClass();
$properties = get_object_vars($object);
$estimatedSize = JsonBufferPool::OBJECT_BASE_SIZE + 
                 (count($properties) * JsonBufferPool::OBJECT_PROPERTY_OVERHEAD);
```

### Primitive Type Sizes

```php
JsonBufferPool::STRING_OVERHEAD;            // 20 - Overhead for string encoding (quotes, escaping)
JsonBufferPool::BOOLEAN_OR_NULL_SIZE;       // 10 - Size for boolean/null values
JsonBufferPool::NUMERIC_SIZE;               // 20 - Size for numeric values (int/float)
JsonBufferPool::DEFAULT_ESTIMATE;           // 100 - Fallback estimate for unknown types
```

**Usage Example:**
```php
$stringData = "Hello, World!";
$estimatedSize = strlen($stringData) + JsonBufferPool::STRING_OVERHEAD;
```

## Threshold Constants

These constants determine when different optimizations and categorizations are applied:

### Array Size Thresholds

```php
JsonBufferPool::SMALL_ARRAY_THRESHOLD;      // 10 - Threshold for small array classification
JsonBufferPool::MEDIUM_ARRAY_THRESHOLD;     // 100 - Threshold for medium array classification  
JsonBufferPool::LARGE_ARRAY_THRESHOLD;      // 1000 - Threshold for large array classification
```

### Pooling Decision Thresholds

```php
JsonBufferPool::POOLING_ARRAY_THRESHOLD;    // 10 - Arrays with 10+ elements use pooling
JsonBufferPool::POOLING_OBJECT_THRESHOLD;   // 5 - Objects with 5+ properties use pooling
JsonBufferPool::POOLING_STRING_THRESHOLD;   // 1024 - Strings longer than 1KB use pooling
```

**Usage Example:**
```php
function shouldUsePooling($data): bool {
    if (is_array($data)) {
        return count($data) >= JsonBufferPool::POOLING_ARRAY_THRESHOLD;
    }
    if (is_object($data)) {
        $vars = get_object_vars($data);
        return $vars && count($vars) >= JsonBufferPool::POOLING_OBJECT_THRESHOLD;
    }
    if (is_string($data)) {
        return strlen($data) > JsonBufferPool::POOLING_STRING_THRESHOLD;
    }
    return false;
}
```

## Buffer Management Constants

### Capacity Constants

```php
JsonBufferPool::MIN_LARGE_BUFFER_SIZE;      // 65536 - Minimum size for very large buffers (64KB)
```

**Usage Example:**
```php
// For very large datasets that exceed standard categories
$estimatedSize = estimateDataSize($largeDataset);
if ($estimatedSize > max($sizeCategories)) {
    $bufferSize = max($estimatedSize * 2, JsonBufferPool::MIN_LARGE_BUFFER_SIZE);
}
```

## Advanced Usage Patterns

### Custom Size Estimation

You can implement custom size estimation using the constants:

```php
class CustomJsonEstimator {
    public static function estimateSize($data): int {
        if (is_string($data)) {
            return strlen($data) + JsonBufferPool::STRING_OVERHEAD;
        }
        
        if (is_array($data)) {
            $count = count($data);
            if ($count === 0) {
                return JsonBufferPool::EMPTY_ARRAY_SIZE;
            }
            
            if ($count < JsonBufferPool::SMALL_ARRAY_THRESHOLD) {
                return JsonBufferPool::SMALL_ARRAY_SIZE;
            } elseif ($count < JsonBufferPool::MEDIUM_ARRAY_THRESHOLD) {
                return JsonBufferPool::MEDIUM_ARRAY_SIZE;
            } elseif ($count < JsonBufferPool::LARGE_ARRAY_THRESHOLD) {
                return JsonBufferPool::LARGE_ARRAY_SIZE;
            } else {
                return JsonBufferPool::XLARGE_ARRAY_SIZE;
            }
        }
        
        if (is_object($data)) {
            $vars = get_object_vars($data);
            return $vars 
                ? count($vars) * JsonBufferPool::OBJECT_PROPERTY_OVERHEAD + JsonBufferPool::OBJECT_BASE_SIZE
                : JsonBufferPool::OBJECT_BASE_SIZE;
        }
        
        if (is_bool($data) || is_null($data)) {
            return JsonBufferPool::BOOLEAN_OR_NULL_SIZE;
        }
        
        if (is_numeric($data)) {
            return JsonBufferPool::NUMERIC_SIZE;
        }
        
        return JsonBufferPool::DEFAULT_ESTIMATE;
    }
}
```

### Testing with Constants

The constants are particularly useful for testing:

```php
class JsonPoolTest extends TestCase {
    public function testArraySizeEstimation(): void {
        $emptyArray = [];
        $estimate = JsonBufferPool::getOptimalCapacity($emptyArray);
        
        // Use constants instead of hardcoded values
        $this->assertEquals(JsonBufferPool::EMPTY_ARRAY_SIZE, $estimate);
    }
    
    public function testPoolingThresholds(): void {
        $smallArray = array_fill(0, 5, 'item');
        $largeArray = array_fill(0, 15, 'item');
        
        // Should not use pooling for small arrays
        $this->assertLessThan(JsonBufferPool::POOLING_ARRAY_THRESHOLD, count($smallArray));
        
        // Should use pooling for large arrays
        $this->assertGreaterThanOrEqual(JsonBufferPool::POOLING_ARRAY_THRESHOLD, count($largeArray));
    }
}
```

### Configuration Validation

Use constants for validating custom configurations:

```php
function validateCustomConfig(array $config): void {
    // Ensure thresholds are consistent with constants
    if (isset($config['pooling_array_threshold'])) {
        if ($config['pooling_array_threshold'] !== JsonBufferPool::POOLING_ARRAY_THRESHOLD) {
            throw new InvalidArgumentException(
                "Custom array threshold conflicts with system constant"
            );
        }
    }
    
    // Validate size categories align with estimation constants
    if (isset($config['size_categories']['small'])) {
        if ($config['size_categories']['small'] < JsonBufferPool::SMALL_ARRAY_SIZE) {
            trigger_error("Small category may be too small for optimal performance", E_USER_WARNING);
        }
    }
}
```

## Performance Monitoring with Constants

Use constants to provide context in monitoring:

```php
function analyzePoolPerformance(): array {
    $stats = JsonBufferPool::getStatistics();
    
    return [
        'reuse_rate' => $stats['reuse_rate'],
        'thresholds' => [
            'array_pooling' => JsonBufferPool::POOLING_ARRAY_THRESHOLD,
            'object_pooling' => JsonBufferPool::POOLING_OBJECT_THRESHOLD,
            'string_pooling' => JsonBufferPool::POOLING_STRING_THRESHOLD
        ],
        'size_estimates' => [
            'small_array' => JsonBufferPool::SMALL_ARRAY_SIZE,
            'medium_array' => JsonBufferPool::MEDIUM_ARRAY_SIZE,
            'large_array' => JsonBufferPool::LARGE_ARRAY_SIZE,
            'xlarge_array' => JsonBufferPool::XLARGE_ARRAY_SIZE
        ],
        'overhead_constants' => [
            'string_overhead' => JsonBufferPool::STRING_OVERHEAD,
            'object_property_overhead' => JsonBufferPool::OBJECT_PROPERTY_OVERHEAD,
            'object_base_size' => JsonBufferPool::OBJECT_BASE_SIZE
        ]
    ];
}
```

## Migration from Hardcoded Values

If you were previously using hardcoded values, migrate to constants:

### Before (Hardcoded)
```php
// ❌ Hardcoded values - fragile and error-prone
if (count($array) >= 10) {
    // Use pooling
}

$stringSize = strlen($data) + 20; // Magic number

$expectedArraySize = 512; // What does this represent?
```

### After (Constants)
```php
// ✅ Using constants - self-documenting and maintainable
if (count($array) >= JsonBufferPool::POOLING_ARRAY_THRESHOLD) {
    // Use pooling
}

$stringSize = strlen($data) + JsonBufferPool::STRING_OVERHEAD;

$expectedArraySize = JsonBufferPool::SMALL_ARRAY_SIZE;
```

## Best Practices

1. **Always use constants** instead of hardcoded values
2. **Reference in tests** to ensure consistency with implementation
3. **Document deviations** if you need different thresholds for specific use cases
4. **Monitor alignment** between your custom logic and the system constants
5. **Update dependencies** when constants change in future versions

## Compatibility

These constants are available starting with PivotPHP Core v1.1.1. They are considered part of the public API and follow semantic versioning:

- **Patch versions**: Values may be fine-tuned for performance
- **Minor versions**: New constants may be added
- **Major versions**: Constants may be removed or significantly changed

Always check the release notes when upgrading to understand any constant changes.

## Related Documentation

- [JSON Optimization Guide](README.md)
- [Performance Tuning Guide](performance-guide.md)
- [Release Notes v1.1.1](../../releases/v1.1.1/RELEASE_NOTES.md)
- [Testing Guide](../../testing/api_testing.md)