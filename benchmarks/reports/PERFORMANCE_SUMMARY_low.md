# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 13:17:19
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 259,068 | 3.86 | 0 B |
| Basic Route Registration (GET) | 166,971 | 5.99 | 84.72 KB |
| Basic Route Registration (POST) | 133,662 | 7.48 | 81.34 KB |
| Route with Parameters (PUT) | 141,843 | 7.05 | 85.34 KB |
| Complex Route Registration | 127,409 | 7.85 | 77.34 KB |
| Route Pattern Matching | 1,923,993 | 0.52 | 0 B |
| Middleware Stack Creation | 116,541 | 8.58 | 93.34 KB |
| Middleware Function Execution | 1,613,194 | 0.62 | 0 B |
| Security Middleware Creation | 152,687 | 6.55 | 77.34 KB |
| CORS Headers Processing | 32,263,877 | 0.03 | 0 B |
| XSS Protection Logic | 3,226,388 | 0.31 | 0 B |
| JWT Token Generation | 188,678 | 5.30 | 0 B |
| JWT Token Validation | 105,597 | 9.47 | 0 B |
| Request Object Creation | 208,776 | 4.79 | 0 B |
| Response Object Creation | 16,777,216 | 0.06 | 0 B |
| Response JSON Setup (100 items) | 127,564 | 7.84 | 0 B |
| JSON Encode (Small) | 8,388,608 | 0.12 | 0 B |
| JSON Encode (Large - 1000 items) | 7,362 | 135.83 | 0 B |
| JSON Decode (Large - 1000 items) | 1,531 | 653.06 | 0 B |
| CORS Configuration Processing | 11,335,957 | 0.09 | 0 B |
| CORS Headers Generation | 19,972,876 | 0.05 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.43 KB
- **Total memory for 100 apps**: 143.05 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Processing with 32,263,877 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
