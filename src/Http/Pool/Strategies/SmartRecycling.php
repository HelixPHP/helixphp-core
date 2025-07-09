<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Pool\Strategies;

/**
 * Smart recycling strategy for aggressive object reuse during high load
 */
class SmartRecycling implements OverflowStrategy
{
    /**
     * Configuration
     */
    private array $config;

    /**
     * Recycling candidates
     */
    private array $recycleCandidates = [];

    /**
     * Metrics
     */
    private array $metrics = [
        'recycled_objects' => 0,
        'recycling_attempts' => 0,
        'recycling_failures' => 0,
        'force_recycled' => 0,
    ];

    /**
     * Object lifecycle tracking
     */
    private array $objectLifecycles = [];

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Check if recycling can help
     */
    public function canHandle(string $type, array $context): bool
    {
        // Only use recycling under high stress
        if (!isset($context['stress_level'])) {
            return false;
        }

        return $context['stress_level'] > 0.8;
    }

    /**
     * Handle by aggressively recycling objects
     */
    public function handle(string $type, array $params): mixed
    {
        $this->metrics['recycling_attempts']++;

        // Try to find recyclable object
        $recycled = $this->findRecyclableObject($type);

        if ($recycled !== null) {
            $this->metrics['recycled_objects']++;
            return $this->resetObject($type, $recycled, $params);
        }

        // Try force recycling if critical
        if ($this->shouldForceRecycle($type, $params)) {
            $recycled = $this->forceRecycle($type);
            if ($recycled !== null) {
                $this->metrics['force_recycled']++;
                return $this->resetObject($type, $recycled, $params);
            }
        }

        $this->metrics['recycling_failures']++;

        // Fall back to creation
        return null;
    }

    /**
     * Track object for potential recycling
     */
    public function trackObject(string $type, mixed $object, array $metadata = []): void
    {
        $id = spl_object_id($object);

        $this->objectLifecycles[$id] = [
            'type' => $type,
            'object' => new \WeakReference($object),
            'created_at' => microtime(true),
            'last_used' => microtime(true),
            'use_count' => 0,
            'metadata' => $metadata,
            'recyclable' => true,
        ];
    }

    /**
     * Mark object as used
     */
    public function markUsed(mixed $object): void
    {
        $id = spl_object_id($object);

        if (isset($this->objectLifecycles[$id])) {
            $this->objectLifecycles[$id]['last_used'] = microtime(true);
            $this->objectLifecycles[$id]['use_count']++;
        }
    }

    /**
     * Find recyclable object
     */
    private function findRecyclableObject(string $type): ?object
    {
        $now = microtime(true);
        $candidates = [];

        foreach ($this->objectLifecycles as $id => $lifecycle) {
            // Check if object still exists
            $object = $lifecycle['object']->get();
            if ($object === null) {
                unset($this->objectLifecycles[$id]);
                continue;
            }

            // Check if correct type and recyclable
            if ($lifecycle['type'] !== $type || !$lifecycle['recyclable']) {
                continue;
            }

            // Calculate recycling score
            $age = $now - $lifecycle['last_used'];
            $score = $this->calculateRecyclingScore($lifecycle, $age);

            $candidates[] = [
                'object' => $object,
                'score' => $score,
                'id' => $id,
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        // Sort by score (higher is better for recycling)
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        // Take the best candidate
        $best = $candidates[0];
        $this->recycleCandidates[$type] = $best['object'];
        unset($this->objectLifecycles[$best['id']]);

        return $best['object'];
    }

    /**
     * Calculate recycling score
     */
    private function calculateRecyclingScore(array $lifecycle, float $age): float
    {
        // Base score from age (older is better)
        $ageScore = min($age / 60, 1.0); // Max score at 60 seconds

        // Penalize heavily used objects
        $usePenalty = min($lifecycle['use_count'] / 100, 0.5);

        // Bonus for objects marked as idle
        $idleBonus = isset($lifecycle['metadata']['idle']) && $lifecycle['metadata']['idle'] ? 0.3 : 0;

        return $ageScore - $usePenalty + $idleBonus;
    }

    /**
     * Check if should force recycle
     */
    private function shouldForceRecycle(string $type, array $params): bool
    {
        // Force recycle for system priority requests
        if (isset($params['priority']) && $params['priority'] >= 90) {
            return true;
        }

        // Force recycle if recycling success rate is good
        $successRate = $this->getRecyclingSuccessRate();
        return $successRate > 0.7;
    }

    /**
     * Force recycle an object
     */
    private function forceRecycle(string $type): ?object
    {
        // Find least recently used object of any type
        $oldest = null;
        $oldestTime = PHP_FLOAT_MAX;
        $oldestId = null;

        foreach ($this->objectLifecycles as $id => $lifecycle) {
            $object = $lifecycle['object']->get();
            if ($object === null) {
                unset($this->objectLifecycles[$id]);
                continue;
            }

            if ($lifecycle['last_used'] < $oldestTime) {
                $oldest = $object;
                $oldestTime = $lifecycle['last_used'];
                $oldestId = $id;
            }
        }

        if ($oldest !== null && $oldestId !== null) {
            unset($this->objectLifecycles[$oldestId]);
            return $oldest;
        }

        return null;
    }

    /**
     * Reset object for reuse
     */
    private function resetObject(string $type, mixed $object, array $params): mixed
    {
        // Type-specific reset logic
        return match ($type) {
            'request' => $this->resetRequest($object, $params),
            'response' => $this->resetResponse($object, $params),
            'uri' => $this->resetUri($object, $params),
            'stream' => $this->resetStream($object, $params),
            default => $object,
        };
    }

    /**
     * Reset request object
     */
    private function resetRequest(mixed $request, array $params): mixed
    {
        // In real implementation, would reset all properties
        return $request;
    }

    /**
     * Reset response object
     */
    private function resetResponse(mixed $response, array $params): mixed
    {
        // In real implementation, would reset all properties
        return $response;
    }

    /**
     * Reset URI object
     */
    private function resetUri(mixed $uri, array $params): mixed
    {
        // URIs are immutable, return as-is
        return $uri;
    }

    /**
     * Reset stream object
     */
    private function resetStream(mixed $stream, array $params): mixed
    {
        if (method_exists($stream, 'rewind')) {
            $stream->rewind();
        }
        return $stream;
    }

    /**
     * Get recycling success rate
     */
    private function getRecyclingSuccessRate(): float
    {
        $total = $this->metrics['recycling_attempts'];

        if ($total === 0) {
            return 0.0;
        }

        return $this->metrics['recycled_objects'] / $total;
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        return array_merge(
            $this->metrics,
            [
                'tracked_objects' => count($this->objectLifecycles),
                'recycling_success_rate' => $this->getRecyclingSuccessRate(),
                'avg_object_age' => $this->getAverageObjectAge(),
                'recycling_efficiency' => $this->calculateEfficiency(),
            ]
        );
    }

    /**
     * Get average object age
     */
    private function getAverageObjectAge(): float
    {
        if (empty($this->objectLifecycles)) {
            return 0.0;
        }

        $now = microtime(true);
        $totalAge = 0;
        $count = 0;

        foreach ($this->objectLifecycles as $lifecycle) {
            $totalAge += $now - $lifecycle['created_at'];
            $count++;
        }

        return $count > 0 ? $totalAge / $count : 0.0;
    }

    /**
     * Calculate recycling efficiency
     */
    private function calculateEfficiency(): float
    {
        $recycled = $this->metrics['recycled_objects'] + $this->metrics['force_recycled'];
        $attempts = $this->metrics['recycling_attempts'];

        if ($attempts === 0) {
            return 0.0;
        }

        // Efficiency considers both success rate and force recycling impact
        $successRate = $recycled / $attempts;
        $forcePenalty = $this->metrics['force_recycled'] / max($recycled, 1);

        return $successRate * (1 - $forcePenalty * 0.3);
    }

    /**
     * Clean up expired lifecycles
     */
    public function cleanup(): void
    {
        $now = microtime(true);
        $maxAge = 300; // 5 minutes

        foreach ($this->objectLifecycles as $id => $lifecycle) {
            // Check if object still exists
            if ($lifecycle['object']->get() === null) {
                unset($this->objectLifecycles[$id]);
                continue;
            }

            // Remove old entries
            if ($now - $lifecycle['created_at'] > $maxAge) {
                unset($this->objectLifecycles[$id]);
            }
        }
    }
}
