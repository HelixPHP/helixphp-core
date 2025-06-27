# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 18:44:25
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 546,133 | 1.83 | 0 B |
| Basic Route Registration (GET) | 324,637 | 3.08 | 81.59 KB |
| Basic Route Registration (POST) | 305,707 | 3.27 | 78.22 KB |
| Route with Parameters (PUT) | 306,825 | 3.26 | 82.22 KB |
| Complex Route Registration | 286,496 | 3.49 | 74.22 KB |
| Route Pattern Matching | 2,438,549 | 0.41 | 0 B |
| Middleware Stack Creation | 226,230 | 4.42 | 90.22 KB |
| Middleware Function Execution | 2,036,070 | 0.49 | 0 B |
| Security Middleware Creation | 306,825 | 3.26 | 74.22 KB |
| CORS Headers Processing | 52,428,800 | 0.02 | 0 B |
| XSS Protection Logic | 4,152,776 | 0.24 | 0 B |
| JWT Token Generation | 230,456 | 4.34 | 0 B |
| JWT Token Validation | 221,219 | 4.52 | 0 B |
| Request Object Creation | 252,517 | 3.96 | 0 B |
| Response Object Creation | 2,279,513 | 0.44 | 0 B |
| Response JSON Setup (100 items) | 174,254 | 5.74 | 0 B |
| JSON Encode (Small) | 9,986,438 | 0.10 | 0 B |
| JSON Encode (Large - 1000 items) | 10,449 | 95.70 | 0 B |
| JSON Decode (Large - 1000 items) | 3,060 | 326.77 | 0 B |
| CORS Configuration Processing | 19,972,876 | 0.05 | 0 B |
| CORS Headers Generation | 52,428,800 | 0.02 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.44 KB
- **Total memory for 100 apps**: 144.3 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Processing with 52,428,800 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
