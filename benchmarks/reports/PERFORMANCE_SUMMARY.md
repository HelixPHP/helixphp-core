# HelixPHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-07-06 14:39:41
- **PHP Version**: 8.4.8
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 95,484 | 10.47 | 2.92 MB |
| Route Registration | 25,523 | 39.18 | 817.05 KB |
| JSON Encode (Small) | 1,087,171 | 0.92 | 0 B |
| JSON Encode (100 items) | 53,157 | 18.81 | 0 B |
| JSON Decode (100 items) | 23,042 | 43.40 | 0 B |
| JWT Token Generation | 114,442 | 8.74 | 0 B |
| JWT Token Validation | 108,946 | 9.18 | 0 B |
| Request Object Creation | 44,877 | 22.28 | 0 B |
| Response Object Creation | 2,582,700 | 0.39 | 0 B |

## Memory Efficiency
- **Memory per app instance**: 5.51 KB
- **Total memory for 50 apps**: 275.34 KB

## Performance Summary
HelixPHP demonstrates excellent performance characteristics for a PHP microframework:

- **Best Performance**: Response Object Creation with 2,582,700 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **JWT Performance**: Fast token generation and validation for authentication
- **JSON Processing**: Efficient handling of API data serialization
