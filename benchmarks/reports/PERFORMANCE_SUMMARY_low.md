# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-28 14:20:03
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 57,205 | 17.48 | 227.34 KB |
| Basic Route Registration (GET) | 25,752 | 38.83 | 316.94 KB |
| Basic Route Registration (POST) | 27,609 | 36.22 | 321.56 KB |
| Route with Parameters (PUT) | 24,630 | 40.60 | 309.56 KB |
| Complex Route Registration | 18,060 | 55.37 | 301.56 KB |
| Route Pattern Matching | 2,557,502 | 0.39 | 0 B |
| Middleware Stack Creation | 18,245 | 54.81 | 498.78 KB |
| Middleware Function Execution | 2,046,002 | 0.49 | 0 B |
| Security Middleware Creation | 23,833 | 41.96 | 301.56 KB |
| CORS Headers Processing | 32,263,877 | 0.03 | 0 B |
| XSS Protection Logic | 4,369,067 | 0.23 | 0 B |
| JWT Token Generation | 253,739 | 3.94 | 0 B |
| JWT Token Validation | 228,324 | 4.38 | 0 B |
| Request Object Creation | 272,357 | 3.67 | 0 B |
| Response Object Creation | 16,777,216 | 0.06 | 0 B |
| Response JSON Setup (100 items) | 175,788 | 5.69 | 0 B |
| JSON Encode (Small) | 9,986,438 | 0.10 | 0 B |
| JSON Encode (Large - 1000 items) | 11,186 | 89.40 | 0 B |
| JSON Decode (Large - 1000 items) | 2,528 | 395.61 | 0 B |
| CORS Configuration Processing | 16,777,216 | 0.06 | 0 B |
| CORS Headers Generation | 52,428,800 | 0.02 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.1 KB
- **Total memory for 100 apps**: 309.62 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 52,428,800 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
