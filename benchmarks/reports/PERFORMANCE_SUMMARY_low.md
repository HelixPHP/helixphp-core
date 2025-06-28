# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-28 14:36:51
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 126,601 | 7.90 | 227.34 KB |
| Basic Route Registration (GET) | 46,906 | 21.32 | 316.94 KB |
| Basic Route Registration (POST) | 57,472 | 17.40 | 321.56 KB |
| Route with Parameters (PUT) | 49,902 | 20.04 | 309.56 KB |
| Complex Route Registration | 38,023 | 26.30 | 301.56 KB |
| Route Pattern Matching | 1,388,842 | 0.72 | 0 B |
| Middleware Stack Creation | 30,095 | 33.23 | 498.78 KB |
| Middleware Function Execution | 925,895 | 1.08 | 0 B |
| Security Middleware Creation | 34,459 | 29.02 | 301.56 KB |
| CORS Headers Processing | 26,214,400 | 0.04 | 0 B |
| XSS Protection Logic | 2,933,080 | 0.34 | 0 B |
| JWT Token Generation | 183,799 | 5.44 | 0 B |
| JWT Token Validation | 123,909 | 8.07 | 0 B |
| Request Object Creation | 126,563 | 7.90 | 0 B |
| Response Object Creation | 9,986,438 | 0.10 | 0 B |
| Response JSON Setup (100 items) | 88,023 | 11.36 | 0 B |
| JSON Encode (Small) | 5,518,821 | 0.18 | 0 B |
| JSON Encode (Large - 1000 items) | 9,252 | 108.08 | 0 B |
| JSON Decode (Large - 1000 items) | 1,971 | 507.27 | 0 B |
| CORS Configuration Processing | 19,972,876 | 0.05 | 0 B |
| CORS Headers Generation | 34,952,533 | 0.03 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.1 KB
- **Total memory for 100 apps**: 309.62 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 34,952,533 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
