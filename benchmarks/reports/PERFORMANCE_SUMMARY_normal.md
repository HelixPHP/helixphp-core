# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-28 14:20:06
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 23,183 | 43.14 | 2.27 MB |
| Basic Route Registration (GET) | 20,643 | 48.44 | -299800 B |
| Basic Route Registration (POST) | 19,935 | 50.16 | 210.38 KB |
| Route with Parameters (PUT) | 10,732 | 93.18 | 242.38 KB |
| Complex Route Registration | 21,691 | 46.10 | 2.94 MB |
| Route Pattern Matching | 1,968,233 | 0.51 | 0 B |
| Middleware Stack Creation | 21,905 | 45.65 | 1.67 MB |
| Middleware Function Execution | 2,004,925 | 0.50 | 0 B |
| Security Middleware Creation | 22,453 | 44.54 | -387776 B |
| CORS Headers Processing | 37,117,735 | 0.03 | 0 B |
| XSS Protection Logic | 3,334,105 | 0.30 | 0 B |
| JWT Token Generation | 230,684 | 4.33 | 0 B |
| JWT Token Validation | 148,898 | 6.72 | 0 B |
| Request Object Creation | 176,335 | 5.67 | 0 B |
| Response Object Creation | 15,887,515 | 0.06 | 0 B |
| Response JSON Setup (100 items) | 153,638 | 6.51 | 0 B |
| JSON Encode (Small) | 10,645,442 | 0.09 | 0 B |
| JSON Encode (Large - 1000 items) | 11,725 | 85.29 | 0 B |
| JSON Decode (Large - 1000 items) | 2,275 | 439.52 | 0 B |
| CORS Configuration Processing | 18,157,160 | 0.06 | 0 B |
| CORS Headers Generation | 38,479,853 | 0.03 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.1 KB
- **Total memory for 100 apps**: 309.62 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 38,479,853 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
