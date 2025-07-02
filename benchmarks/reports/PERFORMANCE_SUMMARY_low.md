# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-07-02 18:56:54
- **PHP Version**: 8.4.8
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 134,433 | 7.44 | 231.25 KB |
| Basic Route Registration (GET) | 26,123 | 38.28 | 315.66 KB |
| Basic Route Registration (POST) | 23,458 | 42.63 | 326.97 KB |
| Route with Parameters (PUT) | 23,958 | 41.74 | 309.47 KB |
| Complex Route Registration | 26,008 | 38.45 | 305.47 KB |
| Route Pattern Matching | 709,696 | 1.41 | 0 B |
| Middleware Stack Creation | 16,923 | 59.09 | 479.06 KB |
| Middleware Function Execution | 201,166 | 4.97 | 0 B |
| Security Middleware Creation | 20,764 | 48.16 | 305.47 KB |
| CORS Headers Processing | 2,173,215 | 0.46 | 0 B |
| XSS Protection Logic | 989,223 | 1.01 | 0 B |
| JWT Token Generation | 104,388 | 9.58 | 0 B |
| JWT Token Validation | 92,589 | 10.80 | 0 B |
| Request Object Creation | 39,203 | 25.51 | 0 B |
| Response Object Creation | 1,010,676 | 0.99 | 0 B |
| Response JSON Setup (100 items) | 88,357 | 11.32 | 0 B |
| JSON Encode (Small) | 1,282,662 | 0.78 | 0 B |
| JSON Encode (Large - 1000 items) | 6,673 | 149.85 | 0 B |
| JSON Decode (Large - 1000 items) | 2,479 | 403.33 | 0 B |
| CORS Configuration Processing | 1,514,189 | 0.66 | 0 B |
| CORS Headers Generation | 2,573,193 | 0.39 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.08 KB
- **Total memory for 100 apps**: 308.02 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 2,573,193 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
