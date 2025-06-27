# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 11:32:56
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 719,435 | 1.39 | 0 B |
| Basic Route Registration (GET) | 467,072 | 2.14 | 81.59 KB |
| Basic Route Registration (POST) | 480,447 | 2.08 | 78.22 KB |
| Route with Parameters (PUT) | 450,516 | 2.22 | 82.22 KB |
| Complex Route Registration | 349,817 | 2.86 | 74.22 KB |
| Route Pattern Matching | 2,621,440 | 0.38 | 0 B |
| Middleware Stack Creation | 194,903 | 5.13 | 90.22 KB |
| Middleware Function Execution | 2,006,844 | 0.50 | 0 B |
| Security Middleware Creation | 368,892 | 2.71 | 74.22 KB |
| CORS Headers Processing | 34,952,533 | 0.03 | 0 B |
| XSS Protection Logic | 4,152,776 | 0.24 | 0 B |
| JWT Token Generation | 171,827 | 5.82 | 0 B |
| JWT Token Validation | 226,230 | 4.42 | 0 B |
| Request Object Creation | 263,296 | 3.80 | 0 B |
| Response Object Creation | 19,972,876 | 0.05 | 0 B |
| Response JSON Setup (100 items) | 161,010 | 6.21 | 0 B |
| JSON Encode (Small) | 9,118,052 | 0.11 | 0 B |
| JSON Encode (Large - 1000 items) | 9,184 | 108.88 | 0 B |
| JSON Decode (Large - 1000 items) | 2,864 | 349.17 | 0 B |
| CORS Configuration Processing | 16,777,216 | 0.06 | 0 B |
| CORS Headers Generation | 52,428,800 | 0.02 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.37 KB
- **Total memory for 100 apps**: 136.8 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 52,428,800 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
