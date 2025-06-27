# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 13:20:25
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 152,531 | 6.56 | 0 B |
| Basic Route Registration (GET) | 77,526 | 12.90 | 816.81 KB |
| Basic Route Registration (POST) | 66,814 | 14.97 | 821.44 KB |
| Route with Parameters (PUT) | 62,232 | 16.07 | 837.44 KB |
| Complex Route Registration | 74,973 | 13.34 | 773.44 KB |
| Route Pattern Matching | 1,077,673 | 0.93 | 0 B |
| Middleware Stack Creation | 63,698 | 15.70 | 933.44 KB |
| Middleware Function Execution | 940,638 | 1.06 | 0 B |
| Security Middleware Creation | 78,519 | 12.74 | 773.44 KB |
| CORS Headers Processing | 16,912,516 | 0.06 | 0 B |
| XSS Protection Logic | 892,215 | 1.12 | 0 B |
| JWT Token Generation | 118,413 | 8.45 | 0 B |
| JWT Token Validation | 100,232 | 9.98 | 0 B |
| Request Object Creation | 118,624 | 8.43 | 0 B |
| Response Object Creation | 10,618,491 | 0.09 | 0 B |
| Response JSON Setup (100 items) | 72,738 | 13.75 | 0 B |
| JSON Encode (Small) | 4,854,519 | 0.21 | 0 B |
| JSON Encode (Large - 1000 items) | 7,010 | 142.65 | 0 B |
| JSON Decode (Large - 1000 items) | 2,032 | 492.23 | 0 B |
| CORS Configuration Processing | 14,315,031 | 0.07 | 0 B |
| CORS Headers Generation | 21,732,145 | 0.05 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.35 KB
- **Total memory for 100 apps**: 135.05 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Generation with 21,732,145 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
