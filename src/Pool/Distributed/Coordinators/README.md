# Distributed Pool Coordinators

## Overview

PivotPHP v1.1.0 supports distributed pool coordination for multi-instance deployments. The core framework provides the interfaces and base implementation, while specific coordination backends (Redis, etcd, Consul, etc.) are implemented via extensions.

## Built-in Coordinators

### NoOpCoordinator
- Default coordinator when no external service is configured
- Provides single-instance operation
- No external dependencies

## Extension-based Coordinators

### Redis Coordinator
Install via: `composer require pivotphp/redis-pool`

```php
// Configuration
$config = [
    'coordination' => 'redis',
    'redis' => [
        'host' => 'localhost',
        'port' => 6379,
        'password' => null,
        'database' => 0,
    ]
];
```

### etcd Coordinator (Coming Soon)
Install via: `composer require pivotphp/etcd-pool`

### Consul Coordinator (Coming Soon)
Install via: `composer require pivotphp/consul-pool`

## Creating Custom Coordinators

To create a custom coordinator, implement the `CoordinatorInterface`:

```php
namespace YourNamespace;

use PivotPHP\Core\Pool\Distributed\Coordinators\CoordinatorInterface;

class CustomCoordinator implements CoordinatorInterface
{
    public function connect(): bool
    {
        // Connect to your coordination service
    }

    public function disconnect(): void
    {
        // Disconnect from service
    }

    // ... implement other interface methods
}
```

## Configuration

```php
use PivotPHP\Core\Pool\Distributed\DistributedPoolManager;

$poolManager = new DistributedPoolManager([
    'coordination' => 'redis',  // or 'none', 'etcd', 'consul', etc.
    'namespace' => 'myapp:pools',
    'sync_interval' => 5,
    'leader_election' => true,
]);
```

## Example Implementation

See `Stubs/RedisCoordinator.php.example` for a complete implementation example.