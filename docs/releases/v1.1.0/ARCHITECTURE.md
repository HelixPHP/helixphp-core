# PivotPHP v1.1.0 Architecture

## High-Performance Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     Application Layer                         │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐    │
│  │   Routes    │  │  Middleware  │  │   Controllers   │    │
│  └─────────────┘  └──────────────┘  └─────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                               │
┌─────────────────────────────────────────────────────────────┐
│                  High-Performance Layer                       │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐    │
│  │Load Shedder │  │Circuit Break │  │  Rate Limiter   │    │
│  └─────────────┘  └──────────────┘  └─────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                               │
┌─────────────────────────────────────────────────────────────┐
│                    Resource Management                        │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐    │
│  │Dynamic Pool │  │Memory Manager│  │   Monitoring    │    │
│  └─────────────┘  └──────────────┘  └─────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                               │
┌─────────────────────────────────────────────────────────────┐
│                      Core Components                          │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐    │
│  │HTTP Factory │  │Request/Resp  │  │     Router      │    │
│  └─────────────┘  └──────────────┘  └─────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

## Component Architecture

### 1. DynamicPool

**Purpose**: Intelligent object lifecycle management with auto-scaling

**Key Components**:
```
DynamicPool
├── Pool Storage (by type)
│   ├── Request Pool
│   ├── Response Pool
│   ├── Stream Pool
│   └── URI Pool
├── Scaling Engine
│   ├── Usage Monitor
│   ├── Expansion Logic
│   └── Shrink Logic
├── Overflow Strategies
│   ├── ElasticExpansion
│   ├── PriorityQueuing
│   ├── GracefulFallback
│   └── SmartRecycling
└── Metrics Collector
    ├── Borrow/Return Stats
    ├── Efficiency Metrics
    └── Performance Data
```

**Flow**:
1. Object requested via `borrow()`
2. Check pool availability
3. If available: reset and return
4. If not: check scaling need
5. If overflow: use strategy
6. Track metrics

### 2. HighPerformanceMode

**Purpose**: Centralized configuration and orchestration

**Components**:
```
HighPerformanceMode
├── Configuration Manager
│   ├── Profile Definitions
│   ├── Custom Settings
│   └── Validation
├── Component Orchestrator
│   ├── Pool Manager
│   ├── Middleware Setup
│   ├── Monitor Config
│   └── Memory Settings
└── Health Monitor
    ├── System Metrics
    ├── Alert System
    └── Diagnostics
```

### 3. LoadShedder Middleware

**Purpose**: Protect system from overload

**Algorithm**:
```
1. Calculate current load
2. Check against threshold
3. If overloaded:
   a. Classify request priority
   b. Apply shedding strategy
   c. Accept/Reject decision
4. Track metrics
5. Adjust dynamically
```

**Strategies**:
- **Priority**: Shed low-priority first
- **Random**: Statistical shedding
- **Oldest**: FIFO shedding
- **Adaptive**: ML-based decisions

### 4. CircuitBreaker Middleware

**State Machine**:
```
         ┌─────────┐
         │ CLOSED  │ ←────── Success threshold met
         └────┬────┘
              │ Failure threshold exceeded
         ┌────▼────┐
         │  OPEN   │
         └────┬────┘
              │ Timeout elapsed
         ┌────▼────┐
         │HALF-OPEN│ ←────── Test recovery
         └─────────┘
              │ Single failure
              └────────────────┐
                              │
                    Returns to OPEN
```

### 5. Memory Management

**Architecture**:
```
MemoryManager
├── Pressure Calculator
│   ├── Memory Usage Monitor
│   ├── Threshold Checker
│   └── Trend Analyzer
├── GC Controller
│   ├── Strategy Selector
│   ├── GC Scheduler
│   └── Emergency Handler
├── Pool Adjuster
│   ├── Size Calculator
│   ├── Rebalancer
│   └── Limit Enforcer
└── Object Tracker
    ├── Lifecycle Monitor
    ├── Reference Counter
    └── Cleanup Scheduler
```

## Data Flow

### Request Processing Flow

```
1. Request arrives
   │
2. Rate Limiter checks
   │
3. Load Shedder evaluates
   │
4. Circuit Breaker validates
   │
5. Borrow objects from pool
   │
6. Process request
   │
7. Return objects to pool
   │
8. Send response
```

### Pool Lifecycle

```
1. Initialization
   ├── Create initial objects
   ├── Configure strategies
   └── Start monitoring

2. Operation
   ├── Borrow/Return cycle
   ├── Auto-scaling
   ├── Overflow handling
   └── Metric collection

3. Maintenance
   ├── Garbage collection
   ├── Pool rebalancing
   ├── Health checks
   └── Cleanup
```

## Performance Optimizations

### 1. Object Pooling

**Before (v1.0.x)**:
```php
// Every request creates new objects
$request = new Request(...);  // Allocation
$response = new Response(...); // Allocation
// ... use objects
// Objects destroyed, memory freed
```

**After (v1.1.0)**:
```php
// Objects reused from pool
$request = $pool->borrow('request');  // No allocation
$response = $pool->borrow('response'); // No allocation
// ... use objects
$pool->return('request', $request);   // Reset for reuse
$pool->return('response', $response); // Reset for reuse
```

### 2. Lazy Initialization

Objects are created only when needed:
```php
class Request {
    private ?ServerRequestInterface $psr7Request = null;
    
    private function getPsr7Request(): ServerRequestInterface {
        if ($this->psr7Request === null) {
            $this->psr7Request = $this->createPsr7Request();
        }
        return $this->psr7Request;
    }
}
```

### 3. Memory-Efficient Structures

**Weak References** for tracking:
```php
$this->trackedObjects[$id] = new \WeakReference($object);
```

**Bounded Collections**:
```php
if (count($this->metrics) > 1000) {
    array_shift($this->metrics); // Remove oldest
}
```

## Scaling Strategies

### Vertical Scaling

Pool sizes adjust based on load:
```
Low Load:  Pool Size = 50
Med Load:  Pool Size = 200  (4x)
High Load: Pool Size = 1000 (20x)
Emergency: Pool Size = 2000 (40x)
```

### Horizontal Scaling

Distributed pools share resources:
```
Instance A: 30% capacity used
Instance B: 80% capacity used
→ B borrows from A automatically
```

## Monitoring Architecture

### Metrics Collection

```
PerformanceMonitor
├── Request Tracking
│   ├── Start/End times
│   ├── Status codes
│   └── Metadata
├── Time Series Data
│   ├── Windowed aggregation
│   ├── Percentile calculation
│   └── Rate computation
└── Export System
    ├── Prometheus format
    ├── Custom formats
    └── Webhook support
```

### Alert System

```
Threshold Monitor
├── Latency Alerts (P99 > threshold)
├── Error Rate Alerts (errors > 5%)
├── Memory Alerts (usage > 80%)
└── Custom Alerts (user-defined)
```

## Security Considerations

### Resource Limits

- Pool sizes are bounded
- Emergency limits prevent runaway growth
- Request priorities prevent starvation

### DoS Protection

- Rate limiting per client
- Load shedding under stress
- Circuit breaking for failing services

## Future Architecture Plans

### v1.2.0 Considerations

1. **Async Pool Operations**
   - Non-blocking borrow/return
   - Promised-based API

2. **Advanced Monitoring**
   - APM integration
   - Distributed tracing

3. **Smart Predictions**
   - ML-based load prediction
   - Preemptive scaling

4. **Multi-Region Support**
   - Geographic pool distribution
   - Regional failover