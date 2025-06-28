# Express PHP Framework - Comprehensive Performance Report

*Generated on: 2025-06-27 22:27:36*

## Test Configuration Overview

| Category | Iterations | Generated |
|----------|------------|-----------|
| **Low** | 100 | 2025-06-27 22:27:24 |
| **Normal** | 1,000 | 2025-06-27 22:27:26 |
| **High** | 10,000 | 2025-06-27 22:27:34 |

## Performance Comparison

| Test | Low (100) | Normal (1K) | High (10K) | Performance Trend |
|------|-----------|-------------|------------|-------------------|
| **App Initialization** | 719,435 ops/s | 617,263 ops/s | 467,686 ops/s | 📉 Declining (35.0%) |
| **Basic Route Registration (GET)** | 114,692 ops/s | 82,960 ops/s | 88,335 ops/s | 📉 Declining (23.0%) |
| **Basic Route Registration (POST)** | 84,375 ops/s | 78,839 ops/s | 92,101 ops/s | 📈 Improving (9.2%) |
| **Route with Parameters (PUT)** | 99,509 ops/s | 97,313 ops/s | 76,078 ops/s | 📉 Declining (23.5%) |
| **Complex Route Registration** | 94,615 ops/s | 60,894 ops/s | 73,135 ops/s | 📉 Declining (22.7%) |
| **Route Pattern Matching** | 2,706,003 ops/s | 2,674,939 ops/s | 2,219,208 ops/s | 📉 Declining (18.0%) |
| **Middleware Stack Creation** | 73,468 ops/s | 53,510 ops/s | 76,184 ops/s | 🔄 Stable |
| **Middleware Function Execution** | 2,219,208 ops/s | 2,216,863 ops/s | 2,232,557 ops/s | 🔄 Stable |
| **Security Middleware Creation** | 80,520 ops/s | 62,901 ops/s | 68,350 ops/s | 📉 Declining (15.1%) |
| **CORS Headers Processing** | 34,952,533 ops/s | 34,379,541 ops/s | 48,998,879 ops/s | 📈 Improving (40.2%) |
| **XSS Protection Logic** | 4,324,025 ops/s | 4,568,959 ops/s | 4,407,633 ops/s | 🔄 Stable |
| **JWT Token Generation** | 273,958 ops/s | 250,197 ops/s | 272,472 ops/s | 🔄 Stable |
| **JWT Token Validation** | 247,598 ops/s | 222,912 ops/s | 233,301 ops/s | 📉 Declining (5.8%) |
| **Request Object Creation** | 291,474 ops/s | 275,398 ops/s | 263,448 ops/s | 📉 Declining (9.6%) |
| **Response Object Creation** | 24,672,376 ops/s | 22,795,130 ops/s | 21,698,417 ops/s | 📉 Declining (12.1%) |
| **Response JSON Setup (100 items)** | 177,951 ops/s | 174,704 ops/s | 132,485 ops/s | 📉 Declining (25.5%) |
| **JSON Encode (Small)** | 11,037,642 ops/s | 10,645,442 ops/s | 4,714,820 ops/s | 📉 Declining (57.3%) |
| **JSON Encode (Large - 1000 items)** | 12,183 ops/s | N/A | 10,469 ops/s | 📉 Declining (14.1%) |
| **JSON Decode (Large - 1000 items)** | 3,065 ops/s | 2,559 ops/s | 2,442 ops/s | 📉 Declining (20.3%) |
| **CORS Configuration Processing** | 19,972,876 ops/s | 19,239,927 ops/s | 17,924,376 ops/s | 📉 Declining (10.3%) |
| **CORS Headers Generation** | 52,428,800 ops/s | 49,932,190 ops/s | 50,533,783 ops/s | 🔄 Stable |
| **Memory Usage** | N/A | N/A | N/A | Insufficient data |

## Top Performers

### 🏆 Highest Average Performance

1. **CORS Headers Generation** - 50,964,925 avg ops/s
2. **CORS Headers Processing** - 39,443,651 avg ops/s
3. **Response Object Creation** - 23,055,308 avg ops/s
4. **CORS Configuration Processing** - 19,045,726 avg ops/s
5. **JSON Encode (Small)** - 8,799,301 avg ops/s

### Key Insights

**🎯 Most Consistent Performance:**
- Basic Route Registration (POST)
- Middleware Function Execution
- XSS Protection Logic

**⚠️ Variable Performance (needs optimization):**
- App Initialization
- Complex Route Registration
- JSON Encode (Small)

## Recommendations

### 🚀 Performance Optimization

1. **Focus on variable performance tests** - These show the most room for improvement
2. **Analyze memory usage patterns** - High memory usage may indicate optimization opportunities
3. **Monitor scalability** - Tests that perform worse with higher iterations need attention

### 📊 Monitoring

1. **Regular benchmarking** - Run comprehensive benchmarks before releases
2. **Performance regression testing** - Compare with baseline results
3. **Load testing** - Use high-iteration results for capacity planning

