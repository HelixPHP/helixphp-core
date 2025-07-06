# HelixPHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-07-02 16:53:57
- **PHP Version**: 8.4.8
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 135,300 | 7.39 | 2.31 MB |
| Basic Route Registration (GET) | 31,103 | 32.15 | -333456 B |
| Basic Route Registration (POST) | 29,190 | 34.26 | 184.69 KB |
| Route with Parameters (PUT) | 33,368 | 29.97 | 200.69 KB |
| Complex Route Registration | 26,280 | 38.05 | 2.98 MB |
| Route Pattern Matching | 739,084 | 1.35 | 0 B |
| Middleware Stack Creation | 25,067 | 39.89 | 1.47 MB |
| Middleware Function Execution | 289,682 | 3.45 | 0 B |
| Security Middleware Creation | 22,335 | 44.77 | -244456 B |
| CORS Headers Processing | 1,642,249 | 0.61 | 0 B |
| XSS Protection Logic | 645,575 | 1.55 | 0 B |
| JWT Token Generation | 85,912 | 11.64 | 0 B |
| JWT Token Validation | 69,532 | 14.38 | 0 B |
| Request Object Creation | 31,371 | 31.88 | 0 B |
| Response Object Creation | 2,755,784 | 0.36 | 0 B |
| Response JSON Setup (100 items) | 110,266 | 9.07 | 0 B |
| JSON Encode (Small) | 1,373,830 | 0.73 | 0 B |
| JSON Encode (Large - 1000 items) | 9,596 | 104.21 | 0 B |
| JSON Decode (Large - 1000 items) | 2,550 | 392.22 | 0 B |
| CORS Configuration Processing | 1,243,494 | 0.80 | 0 B |
| CORS Headers Generation | 2,141,043 | 0.47 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.08 KB
- **Total memory for 100 apps**: 308.02 KB

## Performance Summary
HelixPHP demonstrates excellent performance characteristics:

- **Best Performance**: Response Object Creation with 2,755,784 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
