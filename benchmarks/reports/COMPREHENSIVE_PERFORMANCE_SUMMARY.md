# Express PHP Framework - Comprehensive Performance Report

*Generated on: 2025-06-27 13:21:53*

## Test Configuration Overview

| Category | Iterations | Generated |
|----------|------------|-----------|
| **Low** | 100 | 2025-06-27 13:17:19 |
| **Normal** | 1,000 | 2025-06-27 13:20:25 |
| **High** | 10,000 | 2025-06-27 13:21:23 |

## Performance Comparison

| Test | Low (100) | Normal (1K) | High (10K) | Performance Trend |
|------|-----------|-------------|------------|-------------------|
| **App Initialization** | 259,068 ops/s | 152,531 ops/s | 193,554 ops/s | 📉 Declining (25.3%) |
| **Basic Route Registration (GET)** | 166,971 ops/s | 77,526 ops/s | 113,205 ops/s | 📉 Declining (32.2%) |
| **Basic Route Registration (POST)** | 133,662 ops/s | 66,814 ops/s | 113,411 ops/s | 📉 Declining (15.2%) |
| **Route with Parameters (PUT)** | 141,843 ops/s | 62,232 ops/s | N/A | 📉 Declining (56.1%) |
| **Complex Route Registration** | 127,409 ops/s | 74,973 ops/s | 104,423 ops/s | 📉 Declining (18.0%) |
| **Route Pattern Matching** | 1,923,993 ops/s | 1,077,673 ops/s | 1,879,674 ops/s | 🔄 Stable |
| **Middleware Stack Creation** | 116,541 ops/s | 63,698 ops/s | 102,241 ops/s | 📉 Declining (12.3%) |
| **Middleware Function Execution** | 1,613,194 ops/s | 940,638 ops/s | 1,160,635 ops/s | 📉 Declining (28.1%) |
| **Security Middleware Creation** | 152,687 ops/s | 78,519 ops/s | 114,352 ops/s | 📉 Declining (25.1%) |
| **CORS Headers Processing** | 32,263,877 ops/s | 16,912,516 ops/s | 36,631,476 ops/s | 📈 Improving (13.5%) |
| **XSS Protection Logic** | 3,226,388 ops/s | 892,215 ops/s | 3,223,660 ops/s | 🔄 Stable |
| **JWT Token Generation** | 188,678 ops/s | 118,413 ops/s | 180,239 ops/s | 🔄 Stable |
| **JWT Token Validation** | 105,597 ops/s | 100,232 ops/s | 153,135 ops/s | 📈 Improving (45.0%) |
| **Request Object Creation** | 208,776 ops/s | 118,624 ops/s | 164,740 ops/s | 📉 Declining (21.1%) |
| **Response Object Creation** | 16,777,216 ops/s | 10,618,491 ops/s | 17,863,305 ops/s | 📈 Improving (6.5%) |
| **Response JSON Setup (100 items)** | 127,564 ops/s | 72,738 ops/s | 97,489 ops/s | 📉 Declining (23.6%) |
| **JSON Encode (Small)** | 8,388,608 ops/s | 4,854,519 ops/s | 5,395,991 ops/s | 📉 Declining (35.7%) |
| **JSON Encode (Large - 1000 items)** | 7,362 ops/s | 7,010 ops/s | 7,567 ops/s | 🔄 Stable |
| **JSON Decode (Large - 1000 items)** | 1,531 ops/s | 2,032 ops/s | 2,185 ops/s | 📈 Improving (42.7%) |
| **CORS Configuration Processing** | 11,335,957 ops/s | 14,315,031 ops/s | 10,131,169 ops/s | 📉 Declining (10.6%) |
| **CORS Headers Generation** | 19,972,876 ops/s | 21,732,145 ops/s | 21,410,434 ops/s | 📈 Improving (7.2%) |
| **Memory Usage** | N/A | N/A | N/A | Insufficient data |

## Top Performers

### 🏆 Highest Average Performance

1. **CORS Headers Processing** - 28,602,623 avg ops/s
2. **CORS Headers Generation** - 21,038,485 avg ops/s
3. **Response Object Creation** - 15,086,337 avg ops/s
4. **CORS Configuration Processing** - 11,927,386 avg ops/s
5. **JSON Encode (Small)** - 6,213,039 avg ops/s

### Key Insights

**🎯 Most Consistent Performance:**
- JSON Encode (Large - 1000 items)
- CORS Headers Generation

**⚠️ Variable Performance (needs optimization):**
- App Initialization
- Basic Route Registration (GET)
- Basic Route Registration (POST)

## Recommendations

### 🚀 Performance Optimization

1. **Focus on variable performance tests** - These show the most room for improvement
2. **Analyze memory usage patterns** - High memory usage may indicate optimization opportunities
3. **Monitor scalability** - Tests that perform worse with higher iterations need attention

### 📊 Monitoring

1. **Regular benchmarking** - Run comprehensive benchmarks before releases
2. **Performance regression testing** - Compare with baseline results
3. **Load testing** - Use high-iteration results for capacity planning

