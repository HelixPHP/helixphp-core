# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-07-02 18:18:13
- **PHP Version**: 8.4.8
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 151,474 | 6.60 | 231.25 KB |
| Basic Route Registration (GET) | 32,522 | 30.75 | 315.66 KB |
| Basic Route Registration (POST) | 34,131 | 29.30 | 326.97 KB |
| Route with Parameters (PUT) | 33,490 | 29.86 | 309.47 KB |
| Complex Route Registration | 29,692 | 33.68 | 305.47 KB |
| Route Pattern Matching | 812,850 | 1.23 | 0 B |
| Middleware Stack Creation | 27,700 | 36.10 | 479.06 KB |
| Middleware Function Execution | 306,601 | 3.26 | 0 B |
| Security Middleware Creation | 30,394 | 32.90 | 305.47 KB |
| CORS Headers Processing | 2,706,003 | 0.37 | 0 B |
| XSS Protection Logic | 1,219,274 | 0.82 | 0 B |
| JWT Token Generation | 131,031 | 7.63 | 0 B |
| JWT Token Validation | 112,237 | 8.91 | 0 B |
| Request Object Creation | 43,555 | 22.96 | 0 B |
| Response Object Creation | 2,621,440 | 0.38 | 0 B |
| Response JSON Setup (100 items) | 134,778 | 7.42 | 0 B |
| JSON Encode (Small) | 1,754,939 | 0.57 | 0 B |
| JSON Encode (Large - 1000 items) | 9,413 | 106.24 | 0 B |
| JSON Decode (Large - 1000 items) | 3,017 | 331.44 | 0 B |
| CORS Configuration Processing | 1,565,039 | 0.64 | 0 B |
| CORS Headers Generation | 2,706,003 | 0.37 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.08 KB
- **Total memory for 100 apps**: 308.02 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Processing with 2,706,003 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
