# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-28 14:20:17
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 10,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 25,199 | 39.68 | 131.42 KB |
| Basic Route Registration (GET) | 20,914 | 47.81 | 7.89 MB |
| Basic Route Registration (POST) | 23,176 | 43.15 | 7.77 MB |
| Route with Parameters (PUT) | 23,356 | 42.82 | 7.52 MB |
| Complex Route Registration | 21,879 | 45.71 | 8.27 MB |
| Route Pattern Matching | 2,506,906 | 0.40 | 0 B |
| Middleware Stack Creation | 20,363 | 49.11 | 7.34 MB |
| Middleware Function Execution | 1,465,413 | 0.68 | 0 B |
| Security Middleware Creation | 19,362 | 51.65 | 7.71 MB |
| CORS Headers Processing | 44,667,774 | 0.02 | 0 B |
| XSS Protection Logic | 4,194,723 | 0.24 | 0 B |
| JWT Token Generation | 263,483 | 3.80 | 0 B |
| JWT Token Validation | 229,211 | 4.36 | 0 B |
| Request Object Creation | 268,096 | 3.73 | 0 B |
| Response Object Creation | 23,643,202 | 0.04 | 0 B |
| Response JSON Setup (100 items) | 169,494 | 5.90 | 0 B |
| JSON Encode (Small) | 9,981,685 | 0.10 | 0 B |
| JSON Encode (Large - 1000 items) | 9,174 | 109.00 | 0 B |
| JSON Decode (Large - 1000 items) | 2,491 | 401.39 | 0 B |
| CORS Configuration Processing | 17,425,442 | 0.06 | 0 B |
| CORS Headers Generation | 47,393,266 | 0.02 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.1 KB
- **Total memory for 100 apps**: 309.62 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 47,393,266 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
