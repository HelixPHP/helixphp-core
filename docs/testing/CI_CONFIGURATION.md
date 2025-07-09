# CI/CD Configuration for PivotPHP Tests

## Environment Variables for Test Stability

To ensure stable tests across different environments (local development, CI/CD, production), PivotPHP supports the following environment variables:

### Performance Test Configuration

#### `PIVOTPHP_PERFORMANCE_THRESHOLD`
- **Purpose**: Sets the minimum requests per second threshold for performance tests
- **Default**: 
  - Local development: `500` req/s
  - CI environments: `250` req/s
- **Usage**: `export PIVOTPHP_PERFORMANCE_THRESHOLD=300`

#### `PIVOTPHP_CONCURRENT_REQUESTS`
- **Purpose**: Sets the number of concurrent requests for stress tests
- **Default**: 
  - Local development: `10000` requests
  - CI environments: `5000` requests
- **Usage**: `export PIVOTPHP_CONCURRENT_REQUESTS=3000`

#### `PIVOTPHP_TEST_ITERATIONS`
- **Purpose**: Sets the number of iterations for integration tests
- **Default**: 
  - Local development: `500` iterations
  - CI environments: `250` iterations
- **Usage**: `export PIVOTPHP_TEST_ITERATIONS=100`

## CI Environment Detection

The framework automatically detects CI environments by checking for:
- `CI=true`
- `GITHUB_ACTIONS=true`
- `TRAVIS=true`

When a CI environment is detected, more conservative default values are used to prevent flaky tests.

## Example GitHub Actions Configuration

```yaml
env:
  PIVOTPHP_PERFORMANCE_THRESHOLD: 200
  PIVOTPHP_CONCURRENT_REQUESTS: 2000
  PIVOTPHP_TEST_ITERATIONS: 100
```

## Example Local Development

```bash
# For slower development machines
export PIVOTPHP_PERFORMANCE_THRESHOLD=100
export PIVOTPHP_CONCURRENT_REQUESTS=1000
export PIVOTPHP_TEST_ITERATIONS=50

# Run tests
composer test
```

## Benefits

1. **Consistent Test Results**: Tests adapt to environment capabilities
2. **Reduced Flakiness**: Conservative thresholds in CI environments
3. **Flexibility**: Easy to override for specific needs
4. **Performance Insights**: Still validates performance while being realistic

## Test Groups

Performance tests are organized into groups:
- `@group stress` - High-load stress tests
- `@group high-performance` - Performance validation tests

To skip performance tests in CI:
```bash
vendor/bin/phpunit --exclude-group stress,high-performance
```