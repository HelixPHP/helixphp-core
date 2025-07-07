# PivotPHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-07-06 12:57:47
- **PHP Version**: 8.4.8
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 113,636 | 8.80 | 293.22 KB |
| Basic Route Registration (GET) | 27,300 | 36.63 | 378.63 KB |
| Basic Route Registration (POST) | 29,717 | 33.65 | 394.06 KB |
| Route with Parameters (PUT) | 29,454 | 33.95 | 368.31 KB |
| Complex Route Registration | 26,852 | 37.24 | 404.56 KB |
| Route Pattern Matching | 775,287 | 1.29 | 0 B |
| Middleware Stack Creation | 24,253 | 41.23 | 506.16 KB |
| Middleware Function Execution | 303,057 | 3.30 | 0 B |
| Security Middleware Creation | 24,119 | 41.46 | 373.06 KB |
| CORS Headers Processing | 2,496,610 | 0.40 | 0 B |
| XSS Protection Logic | 1,149,124 | 0.87 | 0 B |
| JWT Token Generation | 125,804 | 7.95 | 0 B |
| JWT Token Validation | 102,350 | 9.77 | 0 B |
| Request Object Creation | 43,365 | 23.06 | 0 B |
| Response Object Creation | 2,438,549 | 0.41 | 0 B |
| Response JSON Setup (100 items) | 125,017 | 8.00 | 0 B |
| JSON Encode (Small) | 1,613,194 | 0.62 | 0 B |
| JSON Encode (Large - 1000 items) | 9,419 | 106.17 | 0 B |
| JSON Decode (Large - 1000 items) | 2,711 | 368.82 | 0 B |
| CORS Configuration Processing | 1,471,686 | 0.68 | 0 B |
| CORS Headers Generation | 2,511,559 | 0.40 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.67 KB
- **Total memory for 100 apps**: 366.62 KB

## Performance Summary
PivotPHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 2,511,559 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
