# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 13:42:28
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 293,863 | 3.40 | 0 B |
| Basic Route Registration (GET) | 107,376 | 9.31 | 816.81 KB |
| Basic Route Registration (POST) | 116,347 | 8.59 | 805.44 KB |
| Route with Parameters (PUT) | 97,408 | 10.27 | 837.44 KB |
| Complex Route Registration | 130,262 | 7.68 | 773.44 KB |
| Route Pattern Matching | 1,957,211 | 0.51 | 0 B |
| Middleware Stack Creation | 86,147 | 11.61 | 933.44 KB |
| Middleware Function Execution | 1,118,779 | 0.89 | 0 B |
| Security Middleware Creation | 160,849 | 6.22 | 773.44 KB |
| CORS Headers Processing | 34,379,541 | 0.03 | 0 B |
| XSS Protection Logic | 2,922,860 | 0.34 | 0 B |
| JWT Token Generation | 112,814 | 8.86 | 0 B |
| JWT Token Validation | 120,436 | 8.30 | 0 B |
| Request Object Creation | 155,425 | 6.43 | 0 B |
| Response Object Creation | 10,538,452 | 0.09 | 0 B |
| Response JSON Setup (100 items) | 90,074 | 11.10 | 0 B |
| JSON Encode (Small) | 8,256,504 | 0.12 | 0 B |
| JSON Encode (Large - 1000 items) | 7,474 | 133.80 | 0 B |
| JSON Decode (Large - 1000 items) | 1,933 | 517.42 | 0 B |
| CORS Configuration Processing | 14,074,846 | 0.07 | 0 B |
| CORS Headers Generation | 37,117,735 | 0.03 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.36 KB
- **Total memory for 100 apps**: 136.3 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 37,117,735 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
