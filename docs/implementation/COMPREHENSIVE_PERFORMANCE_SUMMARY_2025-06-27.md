# Express PHP Framework - Comprehensive Performance Report

*Generated on: 2025-06-27 18:44:36*

## Test Configuration Overview

| Category | Iterations | Generated |
|----------|------------|-----------|
| **Low** | 100 | 2025-06-27 18:44:25 |
| **Normal** | 1,000 | 2025-06-27 18:44:11 |

## Performance Comparison

| Test | Low (100) | Normal (1K) | High (10K) | Performance Trend |
|------|-----------|-------------|------------|-------------------|
| **App Initialization** | 546,133 ops/s | 579,724 ops/s | N/A | 📈 Improving (6.2%) |
| **Basic Route Registration (GET)** | 324,637 ops/s | 327,348 ops/s | N/A | 🔄 Stable |
| **Basic Route Registration (POST)** | 305,707 ops/s | 342,924 ops/s | N/A | 📈 Improving (12.2%) |
| **Route with Parameters (PUT)** | 306,825 ops/s | 325,518 ops/s | N/A | 📈 Improving (6.1%) |
| **Complex Route Registration** | 286,496 ops/s | 317,871 ops/s | N/A | 📈 Improving (11.0%) |
| **Route Pattern Matching** | 2,438,549 ops/s | 2,785,062 ops/s | N/A | 📈 Improving (14.2%) |
| **Middleware Stack Creation** | 226,230 ops/s | 207,259 ops/s | N/A | 📉 Declining (8.4%) |
| **Middleware Function Execution** | 2,036,070 ops/s | 2,037,059 ops/s | N/A | 🔄 Stable |
| **Security Middleware Creation** | 306,825 ops/s | 285,385 ops/s | N/A | 📉 Declining (7.0%) |
| **CORS Headers Processing** | 52,428,800 ops/s | 47,662,545 ops/s | N/A | 📉 Declining (9.1%) |
| **XSS Protection Logic** | 4,152,776 ops/s | 3,998,383 ops/s | N/A | 🔄 Stable |
| **JWT Token Generation** | 230,456 ops/s | 273,067 ops/s | N/A | 📈 Improving (18.5%) |
| **JWT Token Validation** | 221,219 ops/s | 241,663 ops/s | N/A | 📈 Improving (9.2%) |
| **Request Object Creation** | 252,517 ops/s | 170,472 ops/s | N/A | 📉 Declining (32.5%) |
| **Response Object Creation** | 2,279,513 ops/s | 20,360,699 ops/s | N/A | 📈 Improving (793.2%) |
| **Response JSON Setup (100 items)** | 174,254 ops/s | 166,951 ops/s | N/A | 🔄 Stable |
| **JSON Encode (Small)** | 9,986,438 ops/s | 11,618,571 ops/s | N/A | 📈 Improving (16.3%) |
| **JSON Encode (Large - 1000 items)** | 10,449 ops/s | 12,196 ops/s | N/A | 📈 Improving (16.7%) |
| **JSON Decode (Large - 1000 items)** | 3,060 ops/s | 2,757 ops/s | N/A | 📉 Declining (9.9%) |
| **CORS Configuration Processing** | 19,972,876 ops/s | 21,290,883 ops/s | N/A | 📈 Improving (6.6%) |
| **CORS Headers Generation** | 52,428,800 ops/s | 49,932,190 ops/s | N/A | 🔄 Stable |
| **Memory Usage** | N/A | N/A | N/A | Insufficient data |

## Top Performers

### 🏆 Highest Average Performance

1. **CORS Headers Generation** - 51,180,495 avg ops/s
2. **CORS Headers Processing** - 50,045,673 avg ops/s
3. **CORS Configuration Processing** - 20,631,880 avg ops/s
4. **Response Object Creation** - 11,320,106 avg ops/s
5. **JSON Encode (Small)** - 10,802,504 avg ops/s

### Key Insights

**🎯 Most Consistent Performance:**
- App Initialization
- Basic Route Registration (GET)
- Basic Route Registration (POST)

**⚠️ Variable Performance (needs optimization):**
- Request Object Creation
- Response Object Creation

## Recommendations

### 🚀 Performance Optimization

1. **Focus on variable performance tests** - These show the most room for improvement
2. **Analyze memory usage patterns** - High memory usage may indicate optimization opportunities
3. **Monitor scalability** - Tests that perform worse with higher iterations need attention

### 📊 Monitoring

1. **Regular benchmarking** - Run comprehensive benchmarks before releases
2. **Performance regression testing** - Compare with baseline results
3. **Load testing** - Use high-iteration results for capacity planning

