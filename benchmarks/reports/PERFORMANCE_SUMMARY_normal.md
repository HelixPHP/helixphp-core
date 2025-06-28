# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-28 14:36:52
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 90,319 | 11.07 | 2.27 MB |
| Basic Route Registration (GET) | 45,335 | 22.06 | -299800 B |
| Basic Route Registration (POST) | 58,123 | 17.20 | 210.38 KB |
| Route with Parameters (PUT) | 54,762 | 18.26 | 242.38 KB |
| Complex Route Registration | 55,127 | 18.14 | 2.94 MB |
| Route Pattern Matching | 2,097,152 | 0.48 | 0 B |
| Middleware Stack Creation | 46,486 | 21.51 | 1.67 MB |
| Middleware Function Execution | 2,097,152 | 0.48 | 0 B |
| Security Middleware Creation | 45,251 | 22.10 | -387776 B |
| CORS Headers Processing | 45,100,043 | 0.02 | 0 B |
| XSS Protection Logic | 4,350,938 | 0.23 | 0 B |
| JWT Token Generation | 233,588 | 4.28 | 0 B |
| JWT Token Validation | 229,674 | 4.35 | 0 B |
| Request Object Creation | 264,208 | 3.78 | 0 B |
| Response Object Creation | 23,172,950 | 0.04 | 0 B |
| Response JSON Setup (100 items) | 165,046 | 6.06 | 0 B |
| JSON Encode (Small) | 5,262,615 | 0.19 | 0 B |
| JSON Encode (Large - 1000 items) | 11,018 | 90.76 | 0 B |
| JSON Decode (Large - 1000 items) | 2,511 | 398.32 | 0 B |
| CORS Configuration Processing | 18,477,110 | 0.05 | 0 B |
| CORS Headers Generation | 45,100,043 | 0.02 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.1 KB
- **Total memory for 100 apps**: 309.62 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Processing with 45,100,043 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
