<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Pool\Strategies;

/**
 * Priority queuing strategy for pool overflow
 * Queues requests based on priority until objects become available
 */
class PriorityQueuing implements OverflowStrategy
{
    /**
     * Priority levels
     */
    public const PRIORITY_SYSTEM = 100;
    public const PRIORITY_HIGH = 75;
    public const PRIORITY_NORMAL = 50;
    public const PRIORITY_LOW = 25;

    /**
     * Configuration
     */
    private array $config;

    /**
     * Priority queue
     */
    private \SplPriorityQueue $queue;

    /**
     * Waiting requests
     */
    private array $waitingRequests = [];

    /**
     * Metrics
     */
    private array $metrics = [
        'queued_requests' => 0,
        'served_requests' => 0,
        'dropped_requests' => 0,
        'max_queue_size' => 0,
        'total_wait_time' => 0,
    ];

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->queue = new \SplPriorityQueue();
        $this->queue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
    }

    /**
     * Check if priority queuing can handle this request
     */
    public function canHandle(string $type, array $context): bool
    {
        // Check if request has priority information
        if (!isset($context['priority'])) {
            return false;
        }

        // Check queue capacity
        $currentQueueSize = count($this->waitingRequests);
        $maxQueueSize = $this->config['max_queue_size'] ?? 1000;

        return $currentQueueSize < $maxQueueSize;
    }

    /**
     * Handle by queuing the request
     */
    public function handle(string $type, array $params): mixed
    {
        $priority = $params['priority'] ?? self::PRIORITY_NORMAL;
        $timeout = $params['timeout'] ?? 30;

        // Create request context
        $requestId = uniqid('req_', true);
        $request = [
            'id' => $requestId,
            'type' => $type,
            'params' => $params,
            'priority' => $priority,
            'queued_at' => microtime(true),
            'timeout' => $timeout,
            'promise' => new \stdClass(), // Placeholder for promise
        ];

        // Add to queue
        $this->queue->insert($request, $priority);
        $this->waitingRequests[$requestId] = $request;

        $this->metrics['queued_requests']++;
        if (count($this->waitingRequests) > $this->metrics['max_queue_size']) {
            $this->metrics['max_queue_size'] = count($this->waitingRequests);
        }

        // In real implementation, this would return a promise
        // For now, we'll simulate waiting or timeout
        return $this->simulateQueueProcessing($requestId);
    }

    /**
     * Simulate queue processing (in real implementation, this would be async)
     */
    private function simulateQueueProcessing(string $requestId): mixed
    {
        $request = $this->waitingRequests[$requestId];
        $startTime = microtime(true);

        // Check for timeout
        if (microtime(true) - $request['queued_at'] > $request['timeout']) {
            unset($this->waitingRequests[$requestId]);
            $this->metrics['dropped_requests']++;

            throw new \RuntimeException('Request timed out in priority queue');
        }

        // Simulate getting an object from pool
        // In real implementation, this would wait for pool availability
        $waitTime = microtime(true) - $startTime;
        $this->metrics['total_wait_time'] += $waitTime;
        $this->metrics['served_requests']++;

        unset($this->waitingRequests[$requestId]);

        // Return a mock object for now
        return $this->createObject($request['type'], $request['params']);
    }

    /**
     * Process queue when objects become available
     */
    public function processQueue(callable $objectProvider): void
    {
        $now = microtime(true);
        $processed = 0;

        while (!$this->queue->isEmpty()) {
            $item = $this->queue->extract();
            $request = $item['data'];

            // Check timeout
            if ($now - $request['queued_at'] > $request['timeout']) {
                unset($this->waitingRequests[$request['id']]);
                $this->metrics['dropped_requests']++;
                continue;
            }

            // Try to get object
            try {
                $object = $objectProvider($request['type']);
                if ($object !== null) {
                    // Fulfill request
                    $waitTime = $now - $request['queued_at'];
                    $this->metrics['total_wait_time'] += $waitTime;
                    $this->metrics['served_requests']++;
                    unset($this->waitingRequests[$request['id']]);
                    $processed++;
                } else {
                    // No object available, re-queue
                    $this->queue->insert($request, $request['priority']);
                    break;
                }
            } catch (\Exception $e) {
                // Error getting object, drop request
                unset($this->waitingRequests[$request['id']]);
                $this->metrics['dropped_requests']++;
            }
        }
    }

    /**
     * Create object (temporary implementation)
     */
    private function createObject(string $type, array $params): mixed
    {
        // This is a placeholder - in real implementation,
        // this would coordinate with the pool
        return new \stdClass();
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        $avgWaitTime = $this->metrics['served_requests'] > 0
            ? $this->metrics['total_wait_time'] / $this->metrics['served_requests']
            : 0;

        return array_merge(
            $this->metrics,
            [
                'current_queue_size' => count($this->waitingRequests),
                'avg_wait_time' => $avgWaitTime,
                'queue_efficiency' => $this->calculateEfficiency(),
            ]
        );
    }

    /**
     * Calculate queue efficiency
     */
    private function calculateEfficiency(): float
    {
        $total = $this->metrics['served_requests'] + $this->metrics['dropped_requests'];

        if ($total === 0) {
            return 1.0;
        }

        return $this->metrics['served_requests'] / $total;
    }

    /**
     * Get queue status
     */
    public function getQueueStatus(): array
    {
        $priorities = [];
        foreach ($this->waitingRequests as $request) {
            $priority = $this->getPriorityName($request['priority']);
            $priorities[$priority] = ($priorities[$priority] ?? 0) + 1;
        }

        return [
            'total_waiting' => count($this->waitingRequests),
            'by_priority' => $priorities,
            'oldest_wait_time' => $this->getOldestWaitTime(),
        ];
    }

    /**
     * Get priority name
     */
    private function getPriorityName(int $priority): string
    {
        return match (true) {
            $priority >= self::PRIORITY_SYSTEM => 'system',
            $priority >= self::PRIORITY_HIGH => 'high',
            $priority >= self::PRIORITY_NORMAL => 'normal',
            default => 'low',
        };
    }

    /**
     * Get oldest wait time
     */
    private function getOldestWaitTime(): float
    {
        if (empty($this->waitingRequests)) {
            return 0.0;
        }

        $now = microtime(true);
        $oldest = 0.0;

        foreach ($this->waitingRequests as $request) {
            $waitTime = $now - $request['queued_at'];
            if ($waitTime > $oldest) {
                $oldest = $waitTime;
            }
        }

        return $oldest;
    }
}
