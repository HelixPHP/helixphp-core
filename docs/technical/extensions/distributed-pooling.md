# Distributed Pooling Extensions

## Overview

PivotPHP v1.1.0 introduces support for distributed object pooling across multiple application instances. The core framework provides the interfaces and infrastructure, while specific coordination backends are implemented as separate extensions.

## Architecture

```
┌─────────────────────────────────────────────────┐
│                 Application                      │
├─────────────────────────────────────────────────┤
│            HighPerformanceMode                   │
├─────────────────────────────────────────────────┤
│          DistributedPoolManager                  │
├─────────────────────────────────────────────────┤
│          CoordinatorInterface                    │
├─────────────────────────────────────────────────┤
│   NoOpCoordinator │ RedisCoordinator (ext)      │
│                   │ EtcdCoordinator (ext)       │
│                   │ ConsulCoordinator (ext)     │
└─────────────────────────────────────────────────┘
```

## Built-in Support

### NoOpCoordinator
- Default implementation when no external coordination is configured
- Provides single-instance operation
- Zero external dependencies
- Automatically used as fallback

## Available Extensions

### Redis Pool Extension
```bash
composer require pivotphp/redis-pool
```

Features:
- Redis-based coordination
- Leader election
- Distributed queues
- Shared state management

Configuration:
```php
[
    'distributed' => [
        'enabled' => true,
        'coordination' => 'redis',
        'redis' => [
            'host' => 'localhost',
            'port' => 6379,
            'password' => null,
            'database' => 0,
        ]
    ]
]
```

### etcd Pool Extension (Coming Soon)
```bash
composer require pivotphp/etcd-pool
```

### Consul Pool Extension (Coming Soon)
```bash
composer require pivotphp/consul-pool
```

## Creating Custom Extensions

### 1. Implement the Coordinator

```php
namespace YourVendor\YourExtension;

use PivotPHP\Core\Pool\Distributed\Coordinators\CoordinatorInterface;

class CustomCoordinator implements CoordinatorInterface
{
    public function connect(): bool
    {
        // Connect to your backend
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        // Store value with optional TTL
    }

    public function get(string $key): mixed
    {
        // Retrieve value
    }

    // ... implement all interface methods
}
```

### 2. Register with Service Provider

```php
namespace YourVendor\YourExtension;

use PivotPHP\Core\Providers\ServiceProvider;

class CustomPoolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register coordinator factory
        $this->app->bind('pool.coordinator.custom', function($app) {
            return new CustomCoordinator($app->config('distributed'));
        });
    }
}
```

### 3. Package Configuration

Create `composer.json`:
```json
{
    "name": "yourvendor/pivotphp-custom-pool",
    "description": "Custom distributed pool coordinator for PivotPHP",
    "require": {
        "pivotphp/core": "^1.1"
    },
    "autoload": {
        "psr-4": {
            "YourVendor\\YourExtension\\": "src/"
        }
    },
    "extra": {
        "pivotphp": {
            "providers": [
                "YourVendor\\YourExtension\\CustomPoolServiceProvider"
            ]
        }
    }
}
```

## Usage Example

```php
use PivotPHP\Core\Performance\HighPerformanceMode;

// Enable high performance mode with distributed pooling
HighPerformanceMode::enable([
    'distributed' => [
        'enabled' => true,
        'coordination' => 'redis', // or 'custom', 'etcd', etc.
        // ... backend-specific config
    ]
]);
```

## Fallback Behavior

If a coordinator extension is not available or fails to initialize:
1. The system logs a warning
2. Falls back to NoOpCoordinator
3. Continues operation in single-instance mode
4. No application errors occur

## Performance Considerations

- Distributed coordination adds network latency
- Use local pools for hot objects
- Configure appropriate sync intervals
- Monitor network traffic between instances

## Best Practices

1. **Start Simple**: Use NoOpCoordinator for single-instance deployments
2. **Add When Needed**: Only enable distributed pooling for multi-instance deployments
3. **Monitor Performance**: Track coordination overhead
4. **Configure Timeouts**: Set appropriate timeouts for network operations
5. **Handle Failures**: Design for network partitions and coordinator failures