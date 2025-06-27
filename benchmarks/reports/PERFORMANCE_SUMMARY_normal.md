# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 18:44:11
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 579,724 | 1.72 | 0 B |
| Basic Route Registration (GET) | 327,348 | 3.05 | 785.56 KB |
| Basic Route Registration (POST) | 342,924 | 2.92 | 774.19 KB |
| Route with Parameters (PUT) | 325,518 | 3.07 | 806.19 KB |
| Complex Route Registration | 317,871 | 3.15 | 742.19 KB |
| Route Pattern Matching | 2,785,062 | 0.36 | 0 B |
| Middleware Stack Creation | 207,259 | 4.82 | 902.19 KB |
| Middleware Function Execution | 2,037,059 | 0.49 | 0 B |
| Security Middleware Creation | 285,385 | 3.50 | 742.19 KB |
| CORS Headers Processing | 47,662,545 | 0.02 | 0 B |
| XSS Protection Logic | 3,998,383 | 0.25 | 0 B |
| JWT Token Generation | 273,067 | 3.66 | 0 B |
| JWT Token Validation | 241,663 | 4.14 | 0 B |
| Request Object Creation | 170,472 | 5.87 | 0 B |
| Response Object Creation | 20,360,699 | 0.05 | 0 B |
| Response JSON Setup (100 items) | 166,951 | 5.99 | 0 B |
| JSON Encode (Small) | 11,618,571 | 0.09 | 0 B |
| JSON Encode (Large - 1000 items) | 12,196 | 81.99 | 0 B |
| JSON Decode (Large - 1000 items) | 2,757 | 362.77 | 0 B |
| CORS Configuration Processing | 21,290,883 | 0.05 | 0 B |
| CORS Headers Generation | 49,932,190 | 0.02 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.36 KB
- **Total memory for 100 apps**: 136.3 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 49,932,190 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
