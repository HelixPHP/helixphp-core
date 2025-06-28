# Express PHP Framework - Comprehensive Performance Report

*Generated on: 2025-06-28 14:20:19*

## Test Configuration Overview

| Category | Iterations | Generated |
|----------|------------|-----------|
| **Low** | 100 | 2025-06-28 14:20:03 |
| **Normal** | 1,000 | 2025-06-28 14:20:06 |
| **High** | 10,000 | 2025-06-28 14:20:17 |

## Performance Comparison

| Test | Low (100) | Normal (1K) | High (10K) | Performance Trend |
|------|-----------|-------------|------------|-------------------|
| **App Initialization** | 57,205 ops/s | 23,183 ops/s | 25,199 ops/s | 📉 Declining (55.9%) |
| **Basic Route Registration (GET)** | 25,752 ops/s | 20,643 ops/s | 20,914 ops/s | 📉 Declining (18.8%) |
| **Basic Route Registration (POST)** | 27,609 ops/s | 19,935 ops/s | 23,176 ops/s | 📉 Declining (16.1%) |
| **Route with Parameters (PUT)** | 24,630 ops/s | 10,732 ops/s | 23,356 ops/s | 📉 Declining (5.2%) |
| **Complex Route Registration** | 18,060 ops/s | 21,691 ops/s | 21,879 ops/s | 📈 Improving (21.1%) |
| **Route Pattern Matching** | 2,557,502 ops/s | 1,968,233 ops/s | 2,506,906 ops/s | 🔄 Stable |
| **Middleware Stack Creation** | 18,245 ops/s | 21,905 ops/s | 20,363 ops/s | 📈 Improving (11.6%) |
| **Middleware Function Execution** | 2,046,002 ops/s | 2,004,925 ops/s | 1,465,413 ops/s | 📉 Declining (28.4%) |
| **Security Middleware Creation** | 23,833 ops/s | 22,453 ops/s | 19,362 ops/s | 📉 Declining (18.8%) |
| **CORS Headers Processing** | 32,263,877 ops/s | 37,117,735 ops/s | 44,667,774 ops/s | 📈 Improving (38.4%) |
| **XSS Protection Logic** | 4,369,067 ops/s | 3,334,105 ops/s | 4,194,723 ops/s | 🔄 Stable |
| **JWT Token Generation** | 253,739 ops/s | 230,684 ops/s | 263,483 ops/s | 🔄 Stable |
| **JWT Token Validation** | 228,324 ops/s | 148,898 ops/s | 229,211 ops/s | 🔄 Stable |
| **Request Object Creation** | 272,357 ops/s | 176,335 ops/s | 268,096 ops/s | 🔄 Stable |
| **Response Object Creation** | 16,777,216 ops/s | 15,887,515 ops/s | 23,643,202 ops/s | 📈 Improving (40.9%) |
| **Response JSON Setup (100 items)** | 175,788 ops/s | 153,638 ops/s | 169,494 ops/s | 🔄 Stable |
| **JSON Encode (Small)** | 9,986,438 ops/s | 10,645,442 ops/s | 9,981,685 ops/s | 🔄 Stable |
| **JSON Encode (Large - 1000 items)** | 11,186 ops/s | 11,725 ops/s | 9,174 ops/s | 📉 Declining (18.0%) |
| **JSON Decode (Large - 1000 items)** | 2,528 ops/s | 2,275 ops/s | 2,491 ops/s | 🔄 Stable |
| **CORS Configuration Processing** | 16,777,216 ops/s | 18,157,160 ops/s | 17,425,442 ops/s | 🔄 Stable |
| **CORS Headers Generation** | 52,428,800 ops/s | 38,479,853 ops/s | 47,393,266 ops/s | 📉 Declining (9.6%) |
| **Memory Usage** | N/A | N/A | N/A | Insufficient data |

## Top Performers

### 🏆 Highest Average Performance

1. **CORS Headers Generation** - 46,100,640 avg ops/s
2. **CORS Headers Processing** - 38,016,462 avg ops/s
3. **Response Object Creation** - 18,769,311 avg ops/s
4. **CORS Configuration Processing** - 17,453,273 avg ops/s
5. **JSON Encode (Small)** - 10,204,522 avg ops/s

### Key Insights

**🎯 Most Consistent Performance:**
- JWT Token Generation
- Response JSON Setup (100 items)
- JSON Encode (Small)

**⚠️ Variable Performance (needs optimization):**
- App Initialization
- Route with Parameters (PUT)
- JWT Token Validation

## Recommendations

### 🚀 Performance Optimization

1. **Focus on variable performance tests** - These show the most room for improvement
2. **Analyze memory usage patterns** - High memory usage may indicate optimization opportunities
3. **Monitor scalability** - Tests that perform worse with higher iterations need attention

### 📊 Monitoring

1. **Regular benchmarking** - Run comprehensive benchmarks before releases
2. **Performance regression testing** - Compare with baseline results
3. **Load testing** - Use high-iteration results for capacity planning

