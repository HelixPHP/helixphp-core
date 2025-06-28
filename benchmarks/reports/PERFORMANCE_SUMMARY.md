# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 20:01:48
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 381,821 | 2.62 | 0 B |
| Route Registration | 196,344 | 5.09 | 777.88 KB |
| JSON Encode (Small) | 5,882,614 | 0.17 | 0 B |
| JSON Encode (100 items) | 41,946 | 23.84 | 0 B |
| JSON Decode (100 items) | 28,930 | 34.57 | 0 B |
| JWT Token Generation | 287,675 | 3.48 | 0 B |
| JWT Token Validation | 249,944 | 4.00 | 0 B |
| Request Object Creation | 296,921 | 3.37 | 0 B |
| Response Object Creation | 24,966,095 | 0.04 | 0 B |

## Memory Efficiency
- **Memory per app instance**: 2.13 KB
- **Total memory for 50 apps**: 106.51 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics for a PHP microframework:

- **Best Performance**: Response Object Creation with 24,966,095 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **JWT Performance**: Fast token generation and validation for authentication
- **JSON Processing**: Efficient handling of API data serialization
