# PivotPHP v1.1.0 Monitoring Setup Guide

## Overview

This guide covers setting up comprehensive monitoring for PivotPHP v1.1.0 high-performance features, including metrics collection, alerting, and visualization.

## Built-in Monitoring

### Performance Monitor

```php
use PivotPHP\Core\Performance\HighPerformanceMode;

// Access the built-in monitor
$monitor = HighPerformanceMode::getMonitor();

// Get real-time metrics
$liveMetrics = $monitor->getLiveMetrics();
/*
[
    'current_load' => 523.5,      // requests/second
    'pool_utilization' => 0.65,   // 65% pool usage
    'memory_pressure' => 0.45,    // 45% memory used
    'gc_frequency' => 12,         // GCs per minute
    'p99_latency' => 45.2,        // milliseconds
    'error_rate' => 0.002,        // 0.2% errors
    'active_requests' => 47,      // concurrent requests
    'alerts' => [...]             // active alerts
]
*/
```

### Detailed Performance Metrics

```php
$performanceMetrics = $monitor->getPerformanceMetrics();
/*
[
    'latency' => [
        'p50' => 12.5,
        'p90' => 28.3,
        'p95' => 35.7,
        'p99' => 45.2,
        'min' => 2.1,
        'max' => 312.5,
        'avg' => 18.7
    ],
    'throughput' => [
        'rps' => 523.5,
        'success_rate' => 0.998,
        'error_rate' => 0.002
    ],
    'memory' => [
        'current' => 134217728,    // 128MB
        'peak' => 201326592,       // 192MB
        'avg' => 145752064         // 139MB
    ],
    'pool' => [
        'sizes' => [...],
        'efficiency' => [...],
        'usage' => [...]
    ],
    'recommendations' => [
        'Increase pool size for request objects',
        'Consider enabling more aggressive GC'
    ]
]
*/
```

## Metrics Export

### Prometheus Format

```php
// Configure Prometheus export
$monitor->configureExport([
    'format' => 'prometheus',
    'endpoint' => '/metrics',
    'labels' => [
        'app' => 'pivotphp',
        'env' => 'production',
        'instance' => gethostname(),
    ],
]);

// In your metrics endpoint
$app->get('/metrics', function ($req, $res) use ($monitor) {
    $metrics = $monitor->export();
    return $res->text($metrics)->header('Content-Type', 'text/plain');
});
```

**Example output:**
```prometheus
# HELP pivotphp_requests_total Total number of requests
# TYPE pivotphp_requests_total counter
pivotphp_requests_total{app="pivotphp",env="production"} 1523847

# HELP pivotphp_request_duration_seconds Request latency
# TYPE pivotphp_request_duration_seconds histogram
pivotphp_request_duration_seconds_bucket{le="0.005"} 42123
pivotphp_request_duration_seconds_bucket{le="0.01"} 89234
pivotphp_request_duration_seconds_bucket{le="0.025"} 145632
pivotphp_request_duration_seconds_sum 28934.23
pivotphp_request_duration_seconds_count 1523847

# HELP pivotphp_pool_size Current pool sizes
# TYPE pivotphp_pool_size gauge
pivotphp_pool_size{type="request"} 487
pivotphp_pool_size{type="response"} 492
```

### Custom Metrics

```php
// Track business metrics
$monitor->recordMetric('orders_processed', 1, [
    'payment_method' => 'stripe',
    'amount' => 99.99,
]);

$monitor->recordMetric('api_calls', 1, [
    'endpoint' => '/users',
    'method' => 'GET',
    'status' => 200,
]);

// These appear in exports with your tags
```

## Grafana Dashboard

### Dashboard JSON

```json
{
  "dashboard": {
    "title": "PivotPHP Performance",
    "panels": [
      {
        "title": "Request Rate",
        "targets": [
          {
            "expr": "rate(pivotphp_requests_total[5m])"
          }
        ]
      },
      {
        "title": "Latency Percentiles",
        "targets": [
          {
            "expr": "histogram_quantile(0.50, rate(pivotphp_request_duration_seconds_bucket[5m]))",
            "legendFormat": "p50"
          },
          {
            "expr": "histogram_quantile(0.99, rate(pivotphp_request_duration_seconds_bucket[5m]))",
            "legendFormat": "p99"
          }
        ]
      },
      {
        "title": "Pool Efficiency",
        "targets": [
          {
            "expr": "pivotphp_pool_efficiency"
          }
        ]
      },
      {
        "title": "Memory Usage",
        "targets": [
          {
            "expr": "pivotphp_memory_usage_bytes"
          }
        ]
      }
    ]
  }
}
```

## Alerting

### Built-in Alerts

```php
// Configure alert thresholds
$monitor->setAlertThresholds([
    'latency_p99' => 1000,        // Alert if P99 > 1 second
    'error_rate' => 0.05,         // Alert if errors > 5%
    'memory_usage' => 0.8,        // Alert if memory > 80%
    'gc_frequency' => 100,        // Alert if >100 GCs/minute
    'pool_efficiency' => 0.3,     // Alert if efficiency < 30%
]);

// Set up alert handlers
$monitor->onAlert(function ($alert) {
    // Send to monitoring system
    sendToSlack($alert['message'], $alert['severity']);
    logAlert($alert);
    
    // Take automatic action for critical alerts
    if ($alert['severity'] === 'critical') {
        enableEmergencyMode();
    }
});
```

### Prometheus Alerting Rules

```yaml
groups:
  - name: pivotphp
    rules:
      - alert: HighLatency
        expr: histogram_quantile(0.99, rate(pivotphp_request_duration_seconds_bucket[5m])) > 1
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High P99 latency detected"
          description: "P99 latency is {{ $value }}s"
      
      - alert: HighErrorRate
        expr: rate(pivotphp_errors_total[5m]) / rate(pivotphp_requests_total[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High error rate detected"
          description: "Error rate is {{ $value | humanizePercentage }}"
      
      - alert: PoolExhaustion
        expr: pivotphp_pool_efficiency < 0.3
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "Pool efficiency low"
          description: "Pool efficiency is {{ $value | humanizePercentage }}"
```

## Component-Specific Monitoring

### Pool Monitoring

```php
// Get pool-specific metrics
$poolStats = $pool->getStats();

// Monitor pool health
if ($poolStats['metrics']['efficiency'] < 50) {
    // Pool is not being utilized efficiently
    adjustPoolConfiguration();
}

// Track overflow events
if ($poolStats['stats']['emergency_activations'] > 0) {
    // System is under stress
    notifyOperations();
}
```

### Circuit Breaker Monitoring

```php
// Monitor circuit states
$circuitBreaker = $app->getMiddleware('circuit-breaker');
$circuits = $circuitBreaker->getCircuitStatus();

foreach ($circuits as $name => $status) {
    $monitor->recordMetric('circuit_state', 
        $status['state'] === 'open' ? 1 : 0,
        ['circuit' => $name]
    );
    
    if ($status['state'] !== 'closed') {
        alertOnCircuitOpen($name, $status);
    }
}
```

### Load Shedder Monitoring

```php
// Track shedding metrics
$loadShedder = $app->getMiddleware('load-shedder');
$shedderStats = $loadShedder->getStats();

$monitor->recordMetric('requests_shed', 
    $shedderStats['total_shed'],
    ['strategy' => $shedderStats['current_strategy']]
);

// Alert on high shedding
if ($shedderStats['shed_rate'] > 0.2) {
    alert("Shedding 20% of requests", 'warning');
}
```

## Logging Integration

### Structured Logging

```php
// Configure structured logging
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

$logger = new Logger('pivotphp');
$handler = new StreamHandler('php://stdout');
$handler->setFormatter(new JsonFormatter());
$logger->pushHandler($handler);

// Log performance events
$monitor->onEvent(function ($event) use ($logger) {
    $logger->info('performance_event', [
        'type' => $event['type'],
        'data' => $event['data'],
        'timestamp' => $event['timestamp'],
        'metrics' => $event['metrics'],
    ]);
});
```

### Log Aggregation

```json
{
  "timestamp": "2024-01-09T10:30:45.123Z",
  "level": "info",
  "message": "performance_event",
  "context": {
    "type": "pool_expansion",
    "data": {
      "pool_type": "request",
      "old_size": 100,
      "new_size": 200
    },
    "metrics": {
      "usage_before": 0.95,
      "expansion_time": 2.5
    }
  }
}
```

## APM Integration

### New Relic

```php
// Instrument with New Relic
if (extension_loaded('newrelic')) {
    $monitor->onRequestStart(function ($requestId, $metadata) {
        newrelic_start_transaction($metadata['path']);
        newrelic_add_custom_parameter('request_id', $requestId);
    });
    
    $monitor->onRequestEnd(function ($requestId, $statusCode) {
        newrelic_end_transaction();
    });
}
```

### DataDog

```php
// DataDog APM integration
use DataDog\Trace\Tracer;

$tracer = Tracer::getInstance();
$monitor->onRequestStart(function ($requestId, $metadata) use ($tracer) {
    $span = $tracer->startSpan('web.request');
    $span->setTag('request.id', $requestId);
    $span->setTag('http.url', $metadata['path']);
});
```

## Health Checks

### Basic Health Endpoint

```php
$app->get('/health', function ($req, $res) use ($monitor) {
    $health = [
        'status' => 'ok',
        'timestamp' => time(),
        'metrics' => $monitor->getLiveMetrics(),
    ];
    
    // Check critical metrics
    if ($health['metrics']['error_rate'] > 0.1) {
        $health['status'] = 'degraded';
    }
    
    if ($health['metrics']['memory_pressure'] > 0.9) {
        $health['status'] = 'critical';
    }
    
    $statusCode = $health['status'] === 'ok' ? 200 : 503;
    return $res->status($statusCode)->json($health);
});
```

### Detailed Health Check

```php
$app->get('/health/detailed', function ($req, $res) use ($app) {
    $checks = [];
    
    // Pool health
    $poolStats = OptimizedHttpFactory::getPoolStats();
    $checks['pools'] = [
        'status' => $poolStats['efficiency']['request'] > 0.3 ? 'healthy' : 'degraded',
        'efficiency' => $poolStats['efficiency'],
        'sizes' => $poolStats['pool_sizes'],
    ];
    
    // Circuit breaker health
    $circuits = $app->getMiddleware('circuit-breaker')->getCircuitStatus();
    $openCircuits = array_filter($circuits, fn($c) => $c['state'] !== 'closed');
    $checks['circuits'] = [
        'status' => empty($openCircuits) ? 'healthy' : 'degraded',
        'open_circuits' => array_keys($openCircuits),
    ];
    
    // Memory health
    $memoryUsage = memory_get_usage(true) / memory_get_peak_usage(true);
    $checks['memory'] = [
        'status' => $memoryUsage < 0.8 ? 'healthy' : 'warning',
        'usage_percentage' => round($memoryUsage * 100, 2),
    ];
    
    // Overall status
    $overallStatus = 'healthy';
    foreach ($checks as $check) {
        if ($check['status'] !== 'healthy') {
            $overallStatus = 'degraded';
            break;
        }
    }
    
    return $res->json([
        'status' => $overallStatus,
        'checks' => $checks,
        'timestamp' => time(),
    ]);
});
```

## Dashboard Examples

### Terminal Dashboard

```bash
#!/bin/bash
# Simple terminal monitoring

while true; do
    clear
    echo "PivotPHP Performance Monitor"
    echo "============================"
    
    # Get metrics
    METRICS=$(curl -s http://localhost:8080/health/detailed | jq .)
    
    echo "Status: $(echo $METRICS | jq -r .status)"
    echo "Pool Efficiency: $(echo $METRICS | jq -r .checks.pools.efficiency.request)%"
    echo "Memory Usage: $(echo $METRICS | jq -r .checks.memory.usage_percentage)%"
    echo "Open Circuits: $(echo $METRICS | jq -r '.checks.circuits.open_circuits | length')"
    
    sleep 5
done
```

### Web Dashboard

```html
<!DOCTYPE html>
<html>
<head>
    <title>PivotPHP Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>PivotPHP Real-time Monitor</h1>
    <canvas id="metricsChart"></canvas>
    
    <script>
    const ctx = document.getElementById('metricsChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Requests/sec',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
            }, {
                label: 'P99 Latency (ms)',
                data: [],
                borderColor: 'rgb(255, 99, 132)',
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Update every second
    setInterval(async () => {
        const response = await fetch('/api/metrics/live');
        const metrics = await response.json();
        
        const now = new Date().toLocaleTimeString();
        chart.data.labels.push(now);
        chart.data.datasets[0].data.push(metrics.current_load);
        chart.data.datasets[1].data.push(metrics.p99_latency);
        
        // Keep last 60 points
        if (chart.data.labels.length > 60) {
            chart.data.labels.shift();
            chart.data.datasets.forEach(d => d.data.shift());
        }
        
        chart.update();
    }, 1000);
    </script>
</body>
</html>
```

## Best Practices

1. **Start with basic monitoring**: Enable built-in metrics first
2. **Add custom metrics gradually**: Focus on business KPIs
3. **Set realistic alerts**: Avoid alert fatigue
4. **Use sampling in production**: 10% is usually sufficient
5. **Archive old metrics**: Keep detailed data for 7 days, aggregated for 30 days
6. **Monitor the monitors**: Ensure monitoring doesn't impact performance