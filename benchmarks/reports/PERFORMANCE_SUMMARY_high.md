# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 13:21:23
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 10,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 193,554 | 5.17 | 0 B |
| Basic Route Registration (GET) | 113,205 | 8.83 | 8.17 MB |
| Basic Route Registration (POST) | 113,411 | 8.82 | 8.18 MB |
| Route with Parameters (PUT) | -1,656 | -603.71 | 7.55 MB |
| Complex Route Registration | 104,423 | 9.58 | 8.8 MB |
| Route Pattern Matching | 1,879,674 | 0.53 | 0 B |
| Middleware Stack Creation | 102,241 | 9.78 | 7.55 MB |
| Middleware Function Execution | 1,160,635 | 0.86 | 0 B |
| Security Middleware Creation | 114,352 | 8.74 | 7.55 MB |
| CORS Headers Processing | 36,631,476 | 0.03 | 0 B |
| XSS Protection Logic | 3,223,660 | 0.31 | 0 B |
| JWT Token Generation | 180,239 | 5.55 | 0 B |
| JWT Token Validation | 153,135 | 6.53 | 0 B |
| Request Object Creation | 164,740 | 6.07 | 0 B |
| Response Object Creation | 17,863,305 | 0.06 | 0 B |
| Response JSON Setup (100 items) | 97,489 | 10.26 | 0 B |
| JSON Encode (Small) | 5,395,991 | 0.19 | 0 B |
| JSON Encode (Large - 1000 items) | 7,567 | 132.15 | 0 B |
| JSON Decode (Large - 1000 items) | 2,185 | 457.75 | 0 B |
| CORS Configuration Processing | 10,131,169 | 0.10 | 0 B |
| CORS Headers Generation | 21,410,434 | 0.05 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.35 KB
- **Total memory for 100 apps**: 135.05 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Processing with 36,631,476 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
