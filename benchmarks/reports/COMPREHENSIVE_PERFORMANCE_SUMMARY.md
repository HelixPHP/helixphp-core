# Express PHP Framework - Comprehensive Performance Report

*Generated on: 2025-06-28 14:37:02*

## Test Configuration Overview

| Category | Iterations | Generated |
|----------|------------|-----------|
| **Low** | 100 | 2025-06-28 14:36:51 |
| **Normal** | 1,000 | 2025-06-28 14:36:52 |
| **High** | 10,000 | 2025-06-28 14:37:00 |

## Performance Comparison

| Test | Low (100) | Normal (1K) | High (10K) | Performance Trend |
|------|-----------|-------------|------------|-------------------|
| **App Initialization** | 126,601 ops/s | 90,319 ops/s | 124,603 ops/s | 🔄 Stable |
| **Basic Route Registration (GET)** | 46,906 ops/s | 45,335 ops/s | 57,625 ops/s | 📈 Improving (22.9%) |
| **Basic Route Registration (POST)** | 57,472 ops/s | 58,123 ops/s | 49,160 ops/s | 📉 Declining (14.5%) |
| **Route with Parameters (PUT)** | 49,902 ops/s | 54,762 ops/s | 49,727 ops/s | 🔄 Stable |
| **Complex Route Registration** | 38,023 ops/s | 55,127 ops/s | 47,765 ops/s | 📈 Improving (25.6%) |
| **Route Pattern Matching** | 1,388,842 ops/s | 2,097,152 ops/s | 2,567,994 ops/s | 📈 Improving (84.9%) |
| **Middleware Stack Creation** | 30,095 ops/s | 46,486 ops/s | 44,422 ops/s | 📈 Improving (47.6%) |
| **Middleware Function Execution** | 925,895 ops/s | 2,097,152 ops/s | 2,072,080 ops/s | 📈 Improving (123.8%) |
| **Security Middleware Creation** | 34,459 ops/s | 45,251 ops/s | 37,642 ops/s | 📈 Improving (9.2%) |
| **CORS Headers Processing** | 26,214,400 ops/s | 45,100,043 ops/s | 41,486,686 ops/s | 📈 Improving (58.3%) |
| **XSS Protection Logic** | 2,933,080 ops/s | 4,350,938 ops/s | 4,303,174 ops/s | 📈 Improving (46.7%) |
| **JWT Token Generation** | 183,799 ops/s | 233,588 ops/s | 243,849 ops/s | 📈 Improving (32.7%) |
| **JWT Token Validation** | 123,909 ops/s | 229,674 ops/s | 211,752 ops/s | 📈 Improving (70.9%) |
| **Request Object Creation** | 126,563 ops/s | 264,208 ops/s | 233,051 ops/s | 📈 Improving (84.1%) |
| **Response Object Creation** | 9,986,438 ops/s | 23,172,950 ops/s | 21,732,145 ops/s | 📈 Improving (117.6%) |
| **Response JSON Setup (100 items)** | 88,023 ops/s | 165,046 ops/s | 172,637 ops/s | 📈 Improving (96.1%) |
| **JSON Encode (Small)** | 5,518,821 ops/s | 5,262,615 ops/s | 10,672,529 ops/s | 📈 Improving (93.4%) |
| **JSON Encode (Large - 1000 items)** | 9,252 ops/s | 11,018 ops/s | 10,800 ops/s | 📈 Improving (16.7%) |
| **JSON Decode (Large - 1000 items)** | 1,971 ops/s | 2,511 ops/s | 2,595 ops/s | 📈 Improving (31.6%) |
| **CORS Configuration Processing** | 19,972,876 ops/s | 18,477,110 ops/s | 19,382,181 ops/s | 🔄 Stable |
| **CORS Headers Generation** | 34,952,533 ops/s | 45,100,043 ops/s | 47,180,022 ops/s | 📈 Improving (35.0%) |
| **Memory Usage** | N/A | N/A | N/A | Insufficient data |

## Top Performers

### 🏆 Highest Average Performance

1. **CORS Headers Generation** - 42,410,866 avg ops/s
2. **CORS Headers Processing** - 37,600,376 avg ops/s
3. **CORS Configuration Processing** - 19,277,389 avg ops/s
4. **Response Object Creation** - 18,297,178 avg ops/s
5. **JSON Encode (Small)** - 7,151,322 avg ops/s

### Key Insights

**🎯 Most Consistent Performance:**
- Route with Parameters (PUT)
- CORS Configuration Processing

**⚠️ Variable Performance (needs optimization):**
- Complex Route Registration
- Route Pattern Matching
- Middleware Stack Creation

## Recommendations

### 🚀 Performance Optimization

1. **Focus on variable performance tests** - These show the most room for improvement
2. **Analyze memory usage patterns** - High memory usage may indicate optimization opportunities
3. **Monitor scalability** - Tests that perform worse with higher iterations need attention

### 📊 Monitoring

1. **Regular benchmarking** - Run comprehensive benchmarks before releases
2. **Performance regression testing** - Compare with baseline results
3. **Load testing** - Use high-iteration results for capacity planning

