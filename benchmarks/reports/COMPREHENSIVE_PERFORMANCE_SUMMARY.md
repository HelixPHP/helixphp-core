# Express PHP Framework - Comprehensive Performance Report

*Generated on: 2025-07-02 16:54:59*

## Test Configuration Overview

| Category | Iterations | Generated |
|----------|------------|-----------|
| **Low** | 100 | 2025-07-02 16:53:39 |
| **Normal** | 1,000 | 2025-07-02 16:53:57 |
| **High** | 10,000 | 2025-07-02 16:54:23 |

## Performance Comparison

| Test | Low (100) | Normal (1K) | High (10K) | Performance Trend |
|------|-----------|-------------|------------|-------------------|
| **App Initialization** | 63,656 ops/s | 135,300 ops/s | 123,151 ops/s | 📈 Improving (93.5%) |
| **Basic Route Registration (GET)** | 14,306 ops/s | 31,103 ops/s | 31,038 ops/s | 📈 Improving (117.0%) |
| **Basic Route Registration (POST)** | 18,349 ops/s | 29,190 ops/s | 25,710 ops/s | 📈 Improving (40.1%) |
| **Route with Parameters (PUT)** | 22,471 ops/s | 33,368 ops/s | 26,860 ops/s | 📈 Improving (19.5%) |
| **Complex Route Registration** | 12,453 ops/s | 26,280 ops/s | 28,042 ops/s | 📈 Improving (125.2%) |
| **Route Pattern Matching** | 373,159 ops/s | 739,084 ops/s | 726,702 ops/s | 📈 Improving (94.7%) |
| **Middleware Stack Creation** | 23,788 ops/s | 25,067 ops/s | 21,033 ops/s | 📉 Declining (11.6%) |
| **Middleware Function Execution** | 176,975 ops/s | 289,682 ops/s | 266,085 ops/s | 📈 Improving (50.4%) |
| **Security Middleware Creation** | 19,532 ops/s | 22,335 ops/s | 24,984 ops/s | 📈 Improving (27.9%) |
| **CORS Headers Processing** | 1,923,993 ops/s | 1,642,249 ops/s | 1,542,988 ops/s | 📉 Declining (19.8%) |
| **XSS Protection Logic** | 751,667 ops/s | 645,575 ops/s | 645,039 ops/s | 📉 Declining (14.2%) |
| **JWT Token Generation** | 59,739 ops/s | 85,912 ops/s | 123,137 ops/s | 📈 Improving (106.1%) |
| **JWT Token Validation** | 48,310 ops/s | 69,532 ops/s | 117,466 ops/s | 📈 Improving (143.1%) |
| **Request Object Creation** | 31,094 ops/s | 31,371 ops/s | 39,896 ops/s | 📈 Improving (28.3%) |
| **Response Object Creation** | 1,407,485 ops/s | 2,755,784 ops/s | 2,689,001 ops/s | 📈 Improving (91.1%) |
| **Response JSON Setup (100 items)** | 122,247 ops/s | 110,266 ops/s | 123,954 ops/s | 🔄 Stable |
| **JSON Encode (Small)** | 1,559,221 ops/s | 1,373,830 ops/s | 1,725,057 ops/s | 📈 Improving (10.6%) |
| **JSON Encode (Large - 1000 items)** | 6,326 ops/s | 9,596 ops/s | 8,980 ops/s | 📈 Improving (42.0%) |
| **JSON Decode (Large - 1000 items)** | 2,537 ops/s | 2,550 ops/s | 2,571 ops/s | 🔄 Stable |
| **CORS Configuration Processing** | 788,403 ops/s | 1,243,494 ops/s | 1,560,323 ops/s | 📈 Improving (97.9%) |
| **CORS Headers Generation** | 1,100,867 ops/s | 2,141,043 ops/s | 2,644,247 ops/s | 📈 Improving (140.2%) |
| **Memory Usage** | N/A | N/A | N/A | Insufficient data |

## Top Performers

### 🏆 Highest Average Performance

1. **Response Object Creation** - 2,284,090 avg ops/s
2. **CORS Headers Generation** - 1,962,052 avg ops/s
3. **CORS Headers Processing** - 1,703,077 avg ops/s
4. **JSON Encode (Small)** - 1,552,703 avg ops/s
5. **CORS Configuration Processing** - 1,197,407 avg ops/s

### Key Insights

**🎯 Most Consistent Performance:**
- XSS Protection Logic
- Response JSON Setup (100 items)
- JSON Decode (Large - 1000 items)

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

