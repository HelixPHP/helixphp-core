# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-07-06 14:39:52
- **PHP Version**: 8.4.8
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 74,682 | 13.39 | 2.92 MB |
| Basic Route Registration (GET) | 25,112 | 39.82 | 3.74 MB |
| Basic Route Registration (POST) | 21,346 | 46.85 | 3.73 MB |
| Route with Parameters (PUT) | 26,641 | 37.54 | 3.73 MB |
| Complex Route Registration | 24,568 | 40.70 | 3.84 MB |
| Route Pattern Matching | 756,958 | 1.32 | 0 B |
| Middleware Stack Creation | 17,043 | 58.68 | 5.1 MB |
| Middleware Function Execution | 293,513 | 3.41 | 0 B |
| Security Middleware Creation | 17,686 | 56.54 | 3.59 MB |
| CORS Headers Processing | 2,398,115 | 0.42 | 0 B |
| XSS Protection Logic | 1,127,198 | 0.89 | 0 B |
| JWT Token Generation | 122,820 | 8.14 | 0 B |
| JWT Token Validation | 108,790 | 9.19 | 0 B |
| Request Object Creation | 44,721 | 22.36 | 0 B |
| Response Object Creation | 2,267,191 | 0.44 | 0 B |
| Response JSON Setup (100 items) | 123,460 | 8.10 | 0 B |
| JSON Encode (Small) | 1,689,208 | 0.59 | 0 B |
| JSON Encode (Large - 1000 items) | 9,055 | 110.44 | 0 B |
| JSON Decode (Large - 1000 items) | 2,222 | 450.09 | 0 B |
| CORS Configuration Processing | 1,503,874 | 0.66 | 0 B |
| CORS Headers Generation | 2,570,039 | 0.39 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.79 KB
- **Total memory for 100 apps**: 378.62 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 2,570,039 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
