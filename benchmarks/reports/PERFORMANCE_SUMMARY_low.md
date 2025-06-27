# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 13:41:33
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 534,306 | 1.87 | 0 B |
| Basic Route Registration (GET) | 168,311 | 5.94 | 84.72 KB |
| Basic Route Registration (POST) | 111,997 | 8.93 | 81.34 KB |
| Route with Parameters (PUT) | 152,909 | 6.54 | 85.34 KB |
| Complex Route Registration | 168,108 | 5.95 | 77.34 KB |
| Route Pattern Matching | 1,754,939 | 0.57 | 0 B |
| Middleware Stack Creation | 61,763 | 16.19 | 93.34 KB |
| Middleware Function Execution | 1,149,124 | 0.87 | 0 B |
| Security Middleware Creation | 136,578 | 7.32 | 77.34 KB |
| CORS Headers Processing | 34,952,533 | 0.03 | 0 B |
| XSS Protection Logic | 3,039,351 | 0.33 | 0 B |
| JWT Token Generation | 133,492 | 7.49 | 0 B |
| JWT Token Validation | 110,990 | 9.01 | 0 B |
| Request Object Creation | 115,993 | 8.62 | 0 B |
| Response Object Creation | 9,986,438 | 0.10 | 0 B |
| Response JSON Setup (100 items) | 80,197 | 12.47 | 0 B |
| JSON Encode (Small) | 4,766,255 | 0.21 | 0 B |
| JSON Encode (Large - 1000 items) | 6,435 | 155.40 | 0 B |
| JSON Decode (Large - 1000 items) | 1,552 | 644.27 | 0 B |
| CORS Configuration Processing | 14,463,117 | 0.07 | 0 B |
| CORS Headers Generation | 32,263,877 | 0.03 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.44 KB
- **Total memory for 100 apps**: 144.3 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Processing with 34,952,533 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
