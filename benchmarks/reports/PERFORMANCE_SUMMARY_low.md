# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 22:31:24
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 100

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 773,857 | 1.29 | 0 B |
| Basic Route Registration (GET) | 111,107 | 9.00 | 81.59 KB |
| Basic Route Registration (POST) | 107,629 | 9.29 | 78.22 KB |
| Route with Parameters (PUT) | 98,922 | 10.11 | 82.22 KB |
| Complex Route Registration | 92,264 | 10.84 | 74.22 KB |
| Route Pattern Matching | 2,621,440 | 0.38 | 0 B |
| Middleware Stack Creation | 80,893 | 12.36 | 90.22 KB |
| Middleware Function Execution | 2,219,208 | 0.45 | 0 B |
| Security Middleware Creation | 81,112 | 12.33 | 74.22 KB |
| CORS Headers Processing | 52,428,800 | 0.02 | 0 B |
| XSS Protection Logic | 4,510,004 | 0.22 | 0 B |
| JWT Token Generation | 254,354 | 3.93 | 0 B |
| JWT Token Validation | 182,838 | 5.47 | 0 B |
| Request Object Creation | 265,967 | 3.76 | 0 B |
| Response Object Creation | 19,972,876 | 0.05 | 0 B |
| Response JSON Setup (100 items) | 178,253 | 5.61 | 0 B |
| JSON Encode (Small) | 9,986,438 | 0.10 | 0 B |
| JSON Encode (Large - 1000 items) | 11,930 | 83.82 | 0 B |
| JSON Decode (Large - 1000 items) | 1,605 | 623.06 | 0 B |
| CORS Configuration Processing | 19,972,876 | 0.05 | 0 B |
| CORS Headers Generation | 52,428,800 | 0.02 | 0 B |

## Memory Analysis
- **Memory per app instance**: 1.44 KB
- **Total memory for 100 apps**: 144.3 KB

## Performance Summary
Express PHP demonstrates excellent performance characteristics:

- **Best Performance**: CORS Headers Processing with 52,428,800 operations/second
- **Framework Overhead**: Minimal memory usage per application instance
- **Middleware Performance**: Efficient middleware stack execution
- **JWT Performance**: Fast token generation and validation
