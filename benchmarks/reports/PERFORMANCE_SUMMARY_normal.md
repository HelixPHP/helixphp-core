# Express PHP Framework - Performance Benchmark

## Test Environment
- **Date**: 2025-06-27 22:27:26
- **PHP Version**: 8.1.32
- **Memory Limit**: -1
- **Iterations**: 1,000

## Performance Results

| Test | Ops/Second | Avg Time (μs) | Memory Used |
|------|------------|---------------|-------------|
| App Initialization | 617,263 | 1.62 | 0 B |
| Basic Route Registration (GET) | 82,960 | 12.05 | 785.56 KB |
| Basic Route Registration (POST) | 78,839 | 12.68 | 774.19 KB |
| Route with Parameters (PUT) | 97,313 | 10.28 | 806.19 KB |
| Complex Route Registration | 60,894 | 16.42 | 742.19 KB |
| Route Pattern Matching | 2,674,939 | 0.37 | 0 B |
| Middleware Stack Creation | 53,510 | 18.69 | 902.19 KB |
| Middleware Function Execution | 2,216,863 | 0.45 | 0 B |
| Security Middleware Creation | 62,901 | 15.90 | 742.19 KB |
| CORS Headers Processing | 34,379,541 | 0.03 | 0 B |
| XSS Protection Logic | 4,568,959 | 0.22 | 0 B |
| JWT Token Generation | 250,197 | 4.00 | 0 B |
| JWT Token Validation | 222,912 | 4.49 | 0 B |
| Request Object Creation | 275,398 | 3.63 | 0 B |
| Response Object Creation | 22,795,130 | 0.04 | 0 B |
| Response JSON Setup (100 items) | 174,704 | 5.72 | 0 B |
| JSON Encode (Small) | 10,645,442 | 0.09 | 0 B |
| JSON Encode (Large - 1000 items) | -1,672 | -598.22 | 0 B |
| JSON Decode (Large - 1000 items) | 2,559 | 390.82 | 0 B |
| CORS Configuration Processing | 19,239,927 | 0.05 | 0 B |
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
