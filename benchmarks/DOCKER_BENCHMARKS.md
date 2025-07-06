# 🐳 Docker Benchmarks for Express PHP

This document explains how to run standardized benchmarks using Docker to ensure consistent results across different environments.

## 🎯 Overview

The Docker benchmark setup provides:
- **Standardized PHP 8.4 environment** with JIT optimizations
- **Multiple databases** for comprehensive testing:
  - MySQL 8.0
  - PostgreSQL 15
  - MariaDB 11
- **Redis 7** for caching benchmarks
- **Consistent configuration** across all environments
- **Automated result collection** and reporting

## 📋 Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- At least 4GB of available RAM
- 2GB of free disk space

## 🚀 Quick Start

### 1. Build and Run All Benchmarks

```bash
# Build the benchmark image
docker-compose -f docker-compose.benchmark.yml build

# Run all benchmarks
docker-compose -f docker-compose.benchmark.yml up

# Run benchmarks in background
docker-compose -f docker-compose.benchmark.yml up -d
```

### 2. View Results

Results are automatically saved to `benchmarks/results/` directory:

```bash
# List all benchmark results
ls -la benchmarks/results/

# View latest comprehensive report
cat benchmarks/results/comprehensive_benchmark_*.json
```

### 3. Run Specific Benchmarks

```bash
# Run only database benchmarks (MySQL)
docker-compose -f docker-compose.benchmark.yml run app php benchmarks/DatabaseBenchmark.php

# Run multi-database comparison
docker-compose -f docker-compose.benchmark.yml run app php benchmarks/MultiDatabaseBenchmark.php

# Run with custom iterations
docker-compose -f docker-compose.benchmark.yml run -e BENCHMARK_ITERATIONS=5000 app php benchmarks/DatabaseBenchmark.php

# Test specific database
docker-compose -f docker-compose.benchmark.yml run -e DB_DRIVER=pgsql -e DB_HOST=postgres -e DB_PORT=5432 app php benchmarks/DatabaseBenchmark.php
```

## 🔧 Configuration

### Environment Variables

Configure benchmarks via environment variables in `docker-compose.benchmark.yml`:

```yaml
environment:
  - BENCHMARK_ITERATIONS=10000    # Number of iterations per test
  - BENCHMARK_WARMUP=1000         # Warmup iterations
  - PHP_MEMORY_LIMIT=1G           # PHP memory limit
  - PHP_MAX_EXECUTION_TIME=0      # No time limit
```

### Database Configuration

All databases are pre-configured with optimizations for benchmarking:

#### MySQL 8.0
- **Buffer Pool**: 1GB
- **Max Connections**: 1000
- **Character Set**: utf8mb4
- **InnoDB optimizations** for performance

#### PostgreSQL 15
- **Shared Buffers**: 256MB
- **Effective Cache**: 1GB
- **Max Connections**: 200
- **Full-text search** enabled
- **Optimized for SSD** storage

#### MariaDB 11
- **Buffer Pool**: 1GB
- **Max Connections**: 1000
- **Query Cache**: 256MB
- **Aria engine** for cache tables
- **Enhanced InnoDB** settings

### PHP Configuration

PHP is optimized for maximum performance:

- **OPcache**: Enabled with 256MB memory
- **JIT**: Enabled with tracing mode (1255)
- **APCu**: Enabled with 256MB shared memory
- **Memory Limit**: 1GB
- **Error Reporting**: Production mode

## 📊 Available Benchmarks

### 1. DatabaseBenchmark

Tests real database operations on MySQL:
- Simple SELECT queries
- Complex JOIN operations
- Full-text search
- Aggregation queries
- INSERT operations
- Transactions with UPDATE

### 2. MultiDatabaseBenchmark

Compares performance across all databases:
- Tests the same operations on MySQL, PostgreSQL, and MariaDB
- Provides side-by-side comparison
- Identifies the fastest database for each operation
- Generates comprehensive comparison report

### 3. SimpleBenchmark

Basic framework operations:
- Request/Response creation
- Routing performance
- Middleware execution

### 4. PSRPerformanceBenchmark

PSR-15 middleware stack performance:
- Middleware pipeline execution
- PSR-7 message handling

### 5. EnhancedAdvancedOptimizationsBenchmark

Advanced optimization features:
- Zero-copy operations
- Memory mapping
- Cache performance

## 🛠️ Advanced Usage

### Running with Debug Tools

```bash
# Start with PHPMyAdmin and Redis Commander
docker-compose -f docker-compose.benchmark.yml --profile debug up

# Access tools:
# - PHPMyAdmin: http://localhost:8080
# - Redis Commander: http://localhost:8081
```

### Custom Benchmark Script

Create `benchmarks/custom_benchmark.php`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Express\Core\Application;
use Express\Database\PDOConnection;

// Your custom benchmark code here
```

Run it:

```bash
docker-compose -f docker-compose.benchmark.yml run app php benchmarks/custom_benchmark.php
```

### Comparing Results

```bash
# Compare two benchmark runs
php benchmarks/compare_benchmarks.php \
  benchmarks/results/comprehensive_benchmark_2025-07-06_10-00-00.json \
  benchmarks/results/comprehensive_benchmark_2025-07-06_11-00-00.json
```

## 📈 Interpreting Results

### Key Metrics

1. **ops_per_sec**: Operations per second (higher is better)
2. **average**: Average time per operation in seconds
3. **median**: Median time (50th percentile)
4. **p95/p99**: 95th/99th percentile times
5. **min/max**: Minimum and maximum times

### Example Output

```json
{
  "simple_select": {
    "iterations": 1000,
    "average": 0.0012,
    "median": 0.0011,
    "p95": 0.0015,
    "p99": 0.0018,
    "ops_per_sec": 833.33
  }
}
```

## 🔍 Troubleshooting

### Common Issues

1. **MySQL connection failed**
   ```bash
   # Check if MySQL is healthy
   docker-compose -f docker-compose.benchmark.yml ps
   
   # View MySQL logs
   docker-compose -f docker-compose.benchmark.yml logs mysql
   ```

2. **Out of memory**
   ```bash
   # Increase Docker memory limit
   # Docker Desktop: Preferences > Resources > Memory
   
   # Or adjust PHP memory limit
   docker-compose -f docker-compose.benchmark.yml run -e PHP_MEMORY_LIMIT=2G app
   ```

3. **Slow performance**
   ```bash
   # Ensure no other containers are running
   docker ps
   
   # Reset Docker volumes
   docker-compose -f docker-compose.benchmark.yml down -v
   ```

### Logs and Debugging

```bash
# View all logs
docker-compose -f docker-compose.benchmark.yml logs

# Follow logs in real-time
docker-compose -f docker-compose.benchmark.yml logs -f

# Access container shell
docker-compose -f docker-compose.benchmark.yml run app sh
```

## 🧹 Cleanup

```bash
# Stop and remove containers
docker-compose -f docker-compose.benchmark.yml down

# Remove volumes (database data)
docker-compose -f docker-compose.benchmark.yml down -v

# Remove images
docker-compose -f docker-compose.benchmark.yml down --rmi all
```

## 📊 Continuous Benchmarking

For CI/CD integration:

```yaml
# .github/workflows/benchmark.yml
name: Performance Benchmarks

on:
  pull_request:
    branches: [main, performance]

jobs:
  benchmark:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Run benchmarks
      run: |
        docker-compose -f docker-compose.benchmark.yml up --abort-on-container-exit
        
    - name: Upload results
      uses: actions/upload-artifact@v2
      with:
        name: benchmark-results
        path: benchmarks/results/
```

## 🎯 Best Practices

1. **Warm up the environment** - First run may be slower
2. **Run multiple times** - Average results for consistency
3. **Isolate benchmarks** - Close other applications
4. **Use same hardware** - Compare results on same machine
5. **Document changes** - Note configuration differences

## 📚 Additional Resources

- [Express PHP Performance Guide](../docs/performance/README.md)
- [PHP OPcache Documentation](https://www.php.net/manual/en/book.opcache.php)
- [MySQL Performance Schema](https://dev.mysql.com/doc/refman/8.0/en/performance-schema.html)
- [Redis Benchmarking](https://redis.io/docs/management/optimization/benchmarks/)

---

> 💡 **Tip**: For production-like results, run benchmarks on hardware similar to your production environment.