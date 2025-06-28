# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-28 14:37:00
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 10,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 124,603 | 8.03 | 131.42 KB |
| Basic Route Registration (GET) | 57,625 | 17.35 | 7.89 MB |
| Basic Route Registration (POST) | 49,160 | 20.34 | 7.77 MB |
| Route with Parameters (PUT) | 49,727 | 20.11 | 7.52 MB |
| Complex Route Registration | 47,765 | 20.94 | 8.27 MB |
| Route Pattern Matching | 2,567,994 | 0.39 | 0 B |
| Middleware Stack Creation | 44,422 | 22.51 | 7.34 MB |
| Middleware Function Execution | 2,072,080 | 0.48 | 0 B |
| Security Middleware Creation | 37,642 | 26.57 | 7.71 MB |
| CORS Headers Processing | 41,486,686 | 0.02 | 0 B |
| XSS Protection Logic | 4,303,174 | 0.23 | 0 B |
| JWT Token Generation | 243,849 | 4.10 | 0 B |
| JWT Token Validation | 211,752 | 4.72 | 0 B |
| Request Object Creation | 233,051 | 4.29 | 0 B |
| Response Object Creation | 21,732,145 | 0.05 | 0 B |
| Response JSON Setup (100 items) | 172,637 | 5.79 | 0 B |
| JSON Encode (Small) | 10,672,529 | 0.09 | 0 B |
| JSON Encode (Large - 1000 items) | 10,800 | 92.59 | 0 B |
| JSON Decode (Large - 1000 items) | 2,595 | 385.42 | 0 B |
| CORS Configuration Processing | 19,382,181 | 0.05 | 0 B |
| CORS Headers Generation | 47,180,022 | 0.02 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.1 KB
- **Total memory for 100 apps**: 309.62 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 47,180,022 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
