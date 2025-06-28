# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 22:27:34
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 10,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 467,686 | 2.14 | 0 B |
| Basic Route Registration (GET) | 88,335 | 11.32 | 7.87 MB |
| Basic Route Registration (POST) | 92,101 | 10.86 | 7.87 MB |
| Route with Parameters (PUT) | 76,078 | 13.14 | 7.25 MB |
| Complex Route Registration | 73,135 | 13.67 | 8.5 MB |
| Route Pattern Matching | 2,219,208 | 0.45 | 0 B |
| Middleware Stack Creation | 76,184 | 13.13 | 7.25 MB |
| Middleware Function Execution | 2,232,557 | 0.45 | 0 B |
| Security Middleware Creation | 68,350 | 14.63 | 7.25 MB |
| CORS Headers Processing | 48,998,879 | 0.02 | 0 B |
| XSS Protection Logic | 4,407,633 | 0.23 | 0 B |
| JWT Token Generation | 272,472 | 3.67 | 0 B |
| JWT Token Validation | 233,301 | 4.29 | 0 B |
| Request Object Creation | 263,448 | 3.80 | 0 B |
| Response Object Creation | 21,698,417 | 0.05 | 0 B |
| Response JSON Setup (100 items) | 132,485 | 7.55 | 0 B |
| JSON Encode (Small) | 4,714,820 | 0.21 | 0 B |
| JSON Encode (Large - 1000 items) | 10,469 | 95.52 | 0 B |
| JSON Decode (Large - 1000 items) | 2,442 | 409.49 | 0 B |
| CORS Configuration Processing | 17,924,376 | 0.06 | 0 B |
| CORS Headers Generation | 50,533,783 | 0.02 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.36 KB
- **Total memory for 100 apps**: 136.3 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 50,533,783 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
