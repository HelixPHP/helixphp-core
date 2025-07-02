# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-07-02 16:54:23
- **PHP Version**: 8.4.8
- **Memory Limit**: -1
- **Iterations**: 10,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 123,151 | 8.12 | 131.62 KB |
| Basic Route Registration (GET) | 31,038 | 32.22 | 7.65 MB |
| Basic Route Registration (POST) | 25,710 | 38.90 | 7.53 MB |
| Route with Parameters (PUT) | 26,860 | 37.23 | 7.53 MB |
| Complex Route Registration | 28,042 | 35.66 | 7.78 MB |
| Route Pattern Matching | 726,702 | 1.38 | 0 B |
| Middleware Stack Creation | 21,033 | 47.54 | 7.33 MB |
| Middleware Function Execution | 266,085 | 3.76 | 0 B |
| Security Middleware Creation | 24,984 | 40.03 | 7.73 MB |
| CORS Headers Processing | 1,542,988 | 0.65 | 0 B |
| XSS Protection Logic | 645,039 | 1.55 | 0 B |
| JWT Token Generation | 123,137 | 8.12 | 0 B |
| JWT Token Validation | 117,466 | 8.51 | 0 B |
| Request Object Creation | 39,896 | 25.06 | 0 B |
| Response Object Creation | 2,689,001 | 0.37 | 0 B |
| Response JSON Setup (100 items) | 123,954 | 8.07 | 0 B |
| JSON Encode (Small) | 1,725,057 | 0.58 | 0 B |
| JSON Encode (Large - 1000 items) | 8,980 | 111.36 | 0 B |
| JSON Decode (Large - 1000 items) | 2,571 | 388.91 | 0 B |
| CORS Configuration Processing | 1,560,323 | 0.64 | 0 B |
| CORS Headers Generation | 2,644,247 | 0.38 | 0 B |

## Memory Analysis
- **Memory per app instance**: 3.08 KB
- **Total memory for 100 apps**: 308.02 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: Response Object Creation with 2,689,001 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
